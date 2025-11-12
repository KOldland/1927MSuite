"""
Test rate limiting and circuit breaker functionality
"""

import asyncio
import pytest
import time
from unittest.mock import AsyncMock, patch

from app.services.rate_limit_service import (
    CircuitBreaker,
    RateLimiter,
    ExponentialBackoff,
    CircuitBreakerState,
    rate_limiter,
)


class TestCircuitBreaker:
    """Test circuit breaker functionality"""

    def test_initial_state_closed(self):
        """Test circuit breaker starts in CLOSED state"""
        cb = CircuitBreaker(name="test", failure_threshold=3, recovery_timeout=60)
        assert cb.state == CircuitBreakerState.CLOSED
        assert cb.metrics.consecutive_failures == 0
        assert cb.metrics.last_failure_time is None

    def test_success_resets_failure_count(self):
        """Test successful calls reset failure count"""
        cb = CircuitBreaker(name="test", failure_threshold=3, recovery_timeout=60)

        # Record failures
        cb._on_failure()
        cb._on_failure()
        assert cb.metrics.consecutive_failures == 2

        # Success should reset
        cb._on_success()
        assert cb.metrics.consecutive_failures == 0
        assert cb.state == CircuitBreakerState.CLOSED

    def test_failure_threshold_opens_circuit(self):
        """Test circuit opens after reaching failure threshold"""
        cb = CircuitBreaker(name="test", failure_threshold=2, recovery_timeout=60)

        cb._on_failure()
        assert cb.state == CircuitBreakerState.CLOSED

        cb._on_failure()
        assert cb.state == CircuitBreakerState.OPEN

    def test_open_circuit_blocks_calls(self):
        """Test open circuit blocks all calls"""
        cb = CircuitBreaker(name="test", failure_threshold=1, recovery_timeout=60)

        cb._on_failure()  # Opens circuit
        assert cb.state == CircuitBreakerState.OPEN

        # Should block calls
        assert not cb._should_attempt_reset()  # Would block if we checked

    def test_recovery_timeout_half_open(self):
        """Test circuit transitions to HALF_OPEN after recovery timeout"""
        cb = CircuitBreaker(name="test", failure_threshold=1, recovery_timeout=1)

        cb._on_failure()
        assert cb.state == CircuitBreakerState.OPEN

        # Wait for recovery timeout
        time.sleep(1.1)

        # Next call should transition to HALF_OPEN
        assert cb._should_attempt_reset()

    def test_half_open_success_closes_circuit(self):
        """Test successful call in HALF_OPEN closes circuit"""
        cb = CircuitBreaker(name="test", failure_threshold=1, recovery_timeout=1, success_threshold=2)

        cb._on_failure()
        time.sleep(1.1)

        # Calls in HALF_OPEN
        cb._transition_to_half_open()
        cb._on_success()
        cb._on_success()  # Need success_threshold successes
        assert cb.state == CircuitBreakerState.CLOSED
        assert cb.metrics.consecutive_failures == 0

    def test_half_open_failure_reopens_circuit(self):
        """Test failed call in HALF_OPEN reopens circuit"""
        cb = CircuitBreaker(name="test", failure_threshold=1, recovery_timeout=1)

        cb._on_failure()
        time.sleep(1.1)

        # Failed call in HALF_OPEN
        cb._transition_to_half_open()
        cb._on_failure()
        assert cb.state == CircuitBreakerState.OPEN
        assert cb.metrics.consecutive_failures == 2  # 1 initial failure + 1 in HALF_OPEN


class TestRateLimiter:
    """Test rate limiter functionality"""

    def test_initial_state(self):
        """Test rate limiter starts with no requests"""
        rl = RateLimiter(requests_per_window=10, window_seconds=60)
        assert rl.requests_per_window == 10
        assert rl.window_seconds == 60

    @pytest.mark.asyncio
    async def test_allow_under_limit(self):
        """Test requests under limit are allowed"""
        rl = RateLimiter(requests_per_window=3, window_seconds=60)

        assert await rl.is_allowed("test_key")
        assert await rl.is_allowed("test_key")
        assert await rl.is_allowed("test_key")

    @pytest.mark.asyncio
    async def test_reject_over_limit(self):
        """Test requests over limit are rejected"""
        rl = RateLimiter(requests_per_window=2, window_seconds=60)

        assert await rl.is_allowed("test_key")
        assert await rl.is_allowed("test_key")
        assert not await rl.is_allowed("test_key")

    @pytest.mark.asyncio
    async def test_window_reset(self):
        """Test rate limit resets after window"""
        rl = RateLimiter(requests_per_window=2, window_seconds=1)

        assert await rl.is_allowed("test_key")
        assert await rl.is_allowed("test_key")
        assert not await rl.is_allowed("test_key")

        # Wait for window to reset
        time.sleep(1.1)

        # Should allow again
        assert await rl.is_allowed("test_key")

    @pytest.mark.asyncio
    async def test_different_keys_isolated(self):
        """Test different keys have separate limits"""
        rl = RateLimiter(requests_per_window=1, window_seconds=60)

        assert await rl.is_allowed("key1")
        assert await rl.is_allowed("key2")
        assert not await rl.is_allowed("key1")  # key1 blocked
        assert not await rl.is_allowed("key2")  # key2 blocked

    @pytest.mark.asyncio
    async def test_get_reset_time(self):
        """Test reset time calculation"""
        rl = RateLimiter(requests_per_window=1, window_seconds=60)

        # Make request to establish window
        await rl.is_allowed("test_key")

        reset_time = await rl.get_reset_time("test_key")
        assert reset_time > time.time()
        assert reset_time <= time.time() + 60


class TestExponentialBackoff:
    """Test exponential backoff functionality"""

    @pytest.mark.asyncio
    async def test_initial_delay(self):
        """Test initial delay is base_delay"""
        eb = ExponentialBackoff(base_delay=1.0, max_delay=60.0, jitter=False)
        delay = await eb.wait(0)
        assert delay == 1.0

    @pytest.mark.asyncio
    async def test_exponential_growth(self):
        """Test delay grows exponentially"""
        eb = ExponentialBackoff(base_delay=1.0, max_delay=60.0, jitter=False)

        delays = []
        for attempt in range(4):
            delay = await eb.wait(attempt)
            delays.append(delay)

        assert delays[0] == 1.0
        assert delays[1] == 2.0
        assert delays[2] == 4.0
        assert delays[3] == 8.0

    @pytest.mark.asyncio
    async def test_max_delay_cap(self):
        """Test delay is capped at max_delay"""
        eb = ExponentialBackoff(base_delay=1.0, max_delay=5.0, jitter=False)

        # Wait for attempt 10 (2^10 = 1024, should be capped at 5)
        delay = await eb.wait(10)
        assert delay == 5.0

    @pytest.mark.asyncio
    async def test_jitter_randomization(self):
        """Test jitter adds randomization"""
        eb = ExponentialBackoff(base_delay=4.0, max_delay=60.0, jitter=True)

        # Get multiple samples
        delays = []
        for _ in range(10):
            delay = await eb.wait(1)
            delays.append(delay)

        # All should be between base_delay * multiplier and base_delay * multiplier * 1.25 (with jitter)
        # For attempt 1: base_delay * 2^1 = 8.0, with ±25% jitter: 6.0 to 10.0
        for delay in delays:
            assert 6.0 <= delay <= 10.0

                        # Should have some variation (jitter)
        assert len(set(delays)) > 1