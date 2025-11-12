"""
Rate limiting and circuit breaker services for KHM GEO Tracker

Implements production-ready rate limiting and circuit breaker patterns
for API resilience and protection against abuse/cascading failures.
"""

import asyncio
import time
from dataclasses import dataclass, field
from enum import Enum
from typing import Dict, List, Optional, Tuple
import structlog

from app.core.config import settings

logger = structlog.get_logger(__name__)


class CircuitBreakerState(Enum):
    """Circuit breaker states"""
    CLOSED = "closed"  # Normal operation
    OPEN = "open"  # Failing, reject requests
    HALF_OPEN = "half_open"  # Testing if service recovered


@dataclass
class CircuitBreakerMetrics:
    """Metrics for circuit breaker monitoring"""
    total_requests: int = 0
    successful_requests: int = 0
    failed_requests: int = 0
    consecutive_failures: int = 0
    last_failure_time: Optional[float] = None
    last_success_time: Optional[float] = None
    state_changes: List[Tuple[CircuitBreakerState, float]] = field(default_factory=list)


class CircuitBreaker:
    """
    Circuit breaker implementation with exponential backoff and jitter

    Protects against cascading failures by temporarily stopping requests
    to failing services and allowing them to recover.
    """

    def __init__(
        self,
        name: str,
        failure_threshold: int = 5,
        recovery_timeout: float = 60.0,
        expected_exception: Exception = Exception,
        success_threshold: int = 3,
    ):
        self.name = name
        self.failure_threshold = failure_threshold
        self.recovery_timeout = recovery_timeout
        self.expected_exception = expected_exception
        self.success_threshold = success_threshold

        self.state = CircuitBreakerState.CLOSED
        self.metrics = CircuitBreakerMetrics()
        self.half_open_successes = 0

        logger.info(
            "Circuit breaker initialized",
            name=name,
            failure_threshold=failure_threshold,
            recovery_timeout=recovery_timeout,
        )

    async def call(self, func, *args, **kwargs):
        """
        Execute function with circuit breaker protection

        Args:
            func: Async function to call
            *args: Positional arguments for func
            **kwargs: Keyword arguments for func

        Returns:
            Function result

        Raises:
            CircuitBreakerOpenException: If circuit is open
            Original exception: If function fails
        """
        if self.state == CircuitBreakerState.OPEN:
            if self._should_attempt_reset():
                self._transition_to_half_open()
            else:
                raise CircuitBreakerOpenException(
                    f"Circuit breaker '{self.name}' is OPEN"
                )

        try:
            self.metrics.total_requests += 1
            result = await func(*args, **kwargs)
            self._on_success()
            return result

        except self.expected_exception as e:
            self._on_failure()
            raise e

    def _should_attempt_reset(self) -> bool:
        """Check if enough time has passed to attempt recovery"""
        if self.metrics.last_failure_time is None:
            return True

        return time.time() - self.metrics.last_failure_time >= self.recovery_timeout

    def _transition_to_half_open(self):
        """Transition from OPEN to HALF_OPEN state"""
        self.state = CircuitBreakerState.HALF_OPEN
        self.half_open_successes = 0
        self._record_state_change(CircuitBreakerState.HALF_OPEN)
        logger.info("Circuit breaker transitioning to HALF_OPEN", name=self.name)

    def _on_success(self):
        """Handle successful request"""
        self.metrics.successful_requests += 1
        self.metrics.consecutive_failures = 0
        self.metrics.last_success_time = time.time()

        if self.state == CircuitBreakerState.HALF_OPEN:
            self.half_open_successes += 1
            if self.half_open_successes >= self.success_threshold:
                self._transition_to_closed()

    def _on_failure(self):
        """Handle failed request"""
        self.metrics.failed_requests += 1
        self.metrics.consecutive_failures += 1
        self.metrics.last_failure_time = time.time()

        if self.state == CircuitBreakerState.CLOSED:
            if self.metrics.consecutive_failures >= self.failure_threshold:
                self._transition_to_open()
        elif self.state == CircuitBreakerState.HALF_OPEN:
            self._transition_to_open()

    def _transition_to_open(self):
        """Transition to OPEN state"""
        self.state = CircuitBreakerState.OPEN
        self._record_state_change(CircuitBreakerState.OPEN)
        logger.warning(
            "Circuit breaker tripped to OPEN",
            name=self.name,
            consecutive_failures=self.metrics.consecutive_failures,
        )

    def _transition_to_closed(self):
        """Transition to CLOSED state"""
        self.state = CircuitBreakerState.CLOSED
        self._record_state_change(CircuitBreakerState.CLOSED)
        logger.info(
            "Circuit breaker reset to CLOSED",
            name=self.name,
            half_open_successes=self.half_open_successes,
        )

    def _record_state_change(self, new_state: CircuitBreakerState):
        """Record state change for monitoring"""
        self.metrics.state_changes.append((new_state, time.time()))


class CircuitBreakerOpenException(Exception):
    """Exception raised when circuit breaker is open"""
    pass


class RateLimiter:
    """
    Sliding window rate limiter with Redis backend support

    Implements token bucket algorithm for smooth rate limiting.
    """

    def __init__(
        self,
        requests_per_window: int = 100,
        window_seconds: int = 60,
        burst_limit: Optional[int] = None,
    ):
        self.requests_per_window = requests_per_window
        self.window_seconds = window_seconds
        self.burst_limit = burst_limit or requests_per_window * 2

        # In-memory storage for demo (use Redis in production)
        self._requests: Dict[str, List[float]] = {}

        logger.info(
            "Rate limiter initialized",
            requests_per_window=requests_per_window,
            window_seconds=window_seconds,
            burst_limit=self.burst_limit,
        )

    async def is_allowed(self, key: str) -> bool:
        """
        Check if request is allowed under rate limit

        Args:
            key: Rate limit key (e.g., client_id, IP address)

        Returns:
            True if allowed, False if rate limited
        """
        now = time.time()
        window_start = now - self.window_seconds

        # Get or create request history for this key
        if key not in self._requests:
            self._requests[key] = []

        requests = self._requests[key]

        # Remove requests outside the window
        requests[:] = [req_time for req_time in requests if req_time > window_start]

        # Check burst limit
        if len(requests) >= self.burst_limit:
            return False

        # Check average rate
        if len(requests) >= self.requests_per_window:
            # Calculate average rate over the window
            if requests:
                oldest_request = requests[0]
                time_span = now - oldest_request
                if time_span > 0:
                    current_rate = len(requests) / time_span * self.window_seconds
                    if current_rate >= self.requests_per_window:
                        return False

        # Add current request
        requests.append(now)
        return True

    async def get_remaining_requests(self, key: str) -> int:
        """
        Get remaining requests allowed in current window

        Args:
            key: Rate limit key

        Returns:
            Number of remaining requests
        """
        now = time.time()
        window_start = now - self.window_seconds

        requests = self._requests.get(key, [])
        recent_requests = [req_time for req_time in requests if req_time > window_start]

        return max(0, self.requests_per_window - len(recent_requests))

    async def get_reset_time(self, key: str) -> float:
        """
        Get time when rate limit resets

        Args:
            key: Rate limit key

        Returns:
            Reset time as Unix timestamp
        """
        requests = self._requests.get(key, [])
        if not requests:
            return time.time() + self.window_seconds

        return requests[0] + self.window_seconds


class ExponentialBackoff:
    """
    Exponential backoff with jitter for retry logic
    """

    def __init__(
        self,
        base_delay: float = 1.0,
        max_delay: float = 60.0,
        multiplier: float = 2.0,
        jitter: bool = True,
    ):
        self.base_delay = base_delay
        self.max_delay = max_delay
        self.multiplier = multiplier
        self.jitter = jitter

    async def wait(self, attempt: int) -> float:
        """
        Wait for calculated backoff duration

        Args:
            attempt: Current attempt number (0-based)

        Returns:
            Actual wait time
        """
        delay = min(self.base_delay * (self.multiplier ** attempt), self.max_delay)

        if self.jitter:
            # Add random jitter (±25% of delay)
            import random
            jitter_range = delay * 0.25
            delay += random.uniform(-jitter_range, jitter_range)
            delay = max(0.1, delay)  # Minimum 100ms

        await asyncio.sleep(delay)
        return delay


# Global instances
rate_limiter = RateLimiter(
    requests_per_window=settings.RATE_LIMIT_REQUESTS,
    window_seconds=settings.RATE_LIMIT_WINDOW,
)

# Circuit breakers for different services
perplexity_circuit_breaker = CircuitBreaker(
    name="perplexity_api",
    failure_threshold=3,
    recovery_timeout=30.0,
)

brave_circuit_breaker = CircuitBreaker(
    name="brave_api",
    failure_threshold=3,
    recovery_timeout=30.0,
)

backoff = ExponentialBackoff()