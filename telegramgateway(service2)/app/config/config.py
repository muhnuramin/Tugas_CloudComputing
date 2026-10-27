import os
from dotenv import load_dotenv

load_dotenv()

class Settings:
    ENV = os.getenv("ENV", "development")
    APP_NAME = os.getenv("APP_NAME", "fastapi-telegram-pro")
    APP_HOST = os.getenv("APP_HOST", "0.0.0.0")
    APP_PORT = int(os.getenv("APP_PORT", 8000))

    raw_origins = os.getenv("ALLOWED_ORIGINS", "")
    ALLOWED_ORIGINS = []
    if raw_origins:
        try:
            import json
            parsed = json.loads(raw_origins)
            if isinstance(parsed, list):
                ALLOWED_ORIGINS = parsed
            else:
                ALLOWED_ORIGINS = [str(parsed)]
        except Exception:
            ALLOWED_ORIGINS = [o.strip() for o in raw_origins.split(",") if o.strip()]

    TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
    TELEGRAM_WEBHOOK_URL = os.getenv("TELEGRAM_WEBHOOK_URL")
    TELEGRAM_WEBHOOK_SECRET_TOKEN = os.getenv("TELEGRAM_WEBHOOK_SECRET_TOKEN")

    DB_HOST = os.getenv("DB_HOST", "db")
    DB_PORT = int(os.getenv("DB_PORT", 3306))
    DB_USER = os.getenv("DB_USER", "appuser")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "apppass")
    DB_NAME = os.getenv("DB_NAME", "fastapi_tg")

    REDIS_URL = os.getenv("REDIS_URL")

    JWT_SECRET = os.getenv("JWT_SECRET")
    JWT_ALG = os.getenv("JWT_ALG", "HS256")
    ACCESS_TOKEN_EXPIRE_MINUTES = int(os.getenv("ACCESS_TOKEN_EXPIRE_MINUTES", 60))

    LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO")

    @property
    def DATABASE_URL(self) -> str:
        return f"mysql+pymysql://{self.DB_USER}:{self.DB_PASSWORD}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_NAME}"

    @property
    def database_url(self) -> str:
        return self.DATABASE_URL

settings = Settings()

