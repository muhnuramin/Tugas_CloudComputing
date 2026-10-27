from fastapi import APIRouter, Request
from aiogram import types
from app.services.bot_service import bot, dp
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

router = APIRouter()

@router.post("/webhook")
async def telegram_webhook(request: Request):
    try:
        data = await request.json()
        logger.info(f"Telegram update: {data}")
        
        update = types.Update.model_validate(data)
        await dp.feed_update(bot, update)
        
        return {"OK": True}
    
    except Exception as e:
        logger.error(f"Error processing update: {e}")
        return {"Error": False}
