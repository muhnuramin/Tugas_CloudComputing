import hmac, hashlib, time
from typing import Mapping
from app.config.config import settings

def validate_webhook_secret(headers: Mapping[str, str]) -> bool:
    token = settings.TELEGRAM_WEBHOOK_SECRET_TOKEN
    incoming = headers.get("x-telegram-bot-api-secret-token") or headers.get("X-Telegram-Bot-Api-Secret-Token")
    return incoming is not None and hmac.compare_digest(incoming, token)

def validate_webapp_init_data(query: str) -> bool:
    from urllib.parse import parse_qsl
    data = dict(parse_qsl(query, keep_blank_values=True))
    hash_ = data.pop("hash", None)
    if not hash_:
        return False
    data_check_string = "\\n".join(f"{k}={v}" for k, v in sorted(data.items()))
    secret_key = hashlib.sha256(settings.TELEGRAM_BOT_TOKEN.encode()).digest()
    h = hmac.new(secret_key, data_check_string.encode(), hashlib.sha256).hexdigest()
    return hmac.compare_digest(h, hash_)

def is_fresh(auth_date: str, max_age_sec: int = 300) -> bool:
    try:
        return (int(time.time()) - int(auth_date)) <= max_age_sec
    except Exception:
        return False
