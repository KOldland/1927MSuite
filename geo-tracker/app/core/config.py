"""
Application configuration settings
"""

import secrets
from typing import List, Optional, Union
from pydantic import AnyHttpUrl, field_validator, ValidationInfo
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """
    Application settings with environment variable support
    """

    # Project
    PROJECT_NAME: str = "KHM GEO Tracker"
    VERSION: str = "0.1.0"
    API_V1_STR: str = "/api/v1"

    # Server
    SERVER_NAME: str = "KHM GEO Tracker"
    SERVER_HOST: AnyHttpUrl = "http://localhost"
    DEBUG: bool = True

    # CORS
    BACKEND_CORS_ORIGINS: List[AnyHttpUrl] = [
        "http://localhost:3000",  # Next.js dev server
        "http://localhost:8000",  # FastAPI docs
    ]

    @field_validator("BACKEND_CORS_ORIGINS", mode="before")
    @classmethod
    def assemble_cors_origins(
        cls, v: Union[str, List[str]]
    ) -> Union[List[str], str]:
        if isinstance(v, str) and not v.startswith("["):
            return [i.strip() for i in v.split(",")]
        elif isinstance(v, (list, str)):
            return v
        raise ValueError(v)

    # Database
    POSTGRES_SERVER: str = "localhost"
    POSTGRES_USER: str = "geo_tracker"
    POSTGRES_PASSWORD: str = "password"
    POSTGRES_DB: str = "geo_tracker"
    POSTGRES_PORT: int = 5432

    DATABASE_URL: Optional[str] = None

    @property
    def sql_database_url(self) -> str:
        """Generate SQLAlchemy database URL"""
        if self.DATABASE_URL:
            return self.DATABASE_URL
        return (
            f"postgresql://{self.POSTGRES_USER}:{self.POSTGRES_PASSWORD}"
            f"@{self.POSTGRES_SERVER}:{self.POSTGRES_PORT}/{self.POSTGRES_DB}"
        )

    # Redis
    REDIS_HOST: str = "localhost"
    REDIS_PORT: int = 6379
    REDIS_DB: int = 0
    REDIS_PASSWORD: Optional[str] = None

    @property
    def redis_url(self) -> str:
        """Generate Redis URL"""
        auth = f":{self.REDIS_PASSWORD}@" if self.REDIS_PASSWORD else ""
        return f"redis://{auth}{self.REDIS_HOST}:{self.REDIS_PORT}/{self.REDIS_DB}"

    # JWT
    SECRET_KEY: str = secrets.token_urlsafe(32)
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 60 * 24 * 7  # 7 days

    # OpenAI
    OPENAI_API_KEY: Optional[str] = None
    OPENAI_MODEL: str = "text-embedding-ada-002"

    # AI Engine API Keys
    PERPLEXITY_API_KEY: Optional[str] = None
    BRAVE_API_KEY: Optional[str] = None
    BING_API_KEY: Optional[str] = None

    # Rate Limiting
    RATE_LIMIT_REQUESTS: int = 100
    RATE_LIMIT_WINDOW: int = 60  # seconds

    # Collector Settings
    COLLECTOR_TIMEOUT: int = 30  # seconds
    COLLECTOR_MAX_RETRIES: int = 3
    COLLECTOR_BACKOFF_FACTOR: float = 2.0

    # Batch Processing
    BATCH_SIZE: int = 100
    MAX_WORKERS: int = 4

    # Monitoring
    PROMETHEUS_PORT: int = 9090
    SENTRY_DSN: Optional[str] = None

    # Slack Alerts
    SLACK_WEBHOOK_URL: Optional[str] = None

    # Timezone
    TIMEZONE: str = "UTC"

    class Config:
        env_file = ".env"
        case_sensitive = True


# Create global settings instance
settings = Settings()