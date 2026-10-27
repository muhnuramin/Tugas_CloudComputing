from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.config.config import settings
from app.routers import health, telegram_webhook, webapp, message

app = FastAPI(title=settings.APP_NAME)

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS or [],
    allow_credentials=True,
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)

app.include_router(health.router)
app.include_router(telegram_webhook.router)
app.include_router(webapp.router)
app.include_router(message.router)

@app.get("/")
async def root():
    return {"name": settings.APP_NAME, "env": settings.ENV}
