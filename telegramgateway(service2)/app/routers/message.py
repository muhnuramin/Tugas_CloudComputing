from fastapi import APIRouter, HTTPException, status
from app.schemas.message import SendMessageRequest, SendMessageResponse
from app.services.bot_service import bot
import logging

logger = logging.getLogger(__name__)

router = APIRouter(tags=["messages"])

@router.post("/send-message", response_model=SendMessageResponse)
@router.post("/messages", response_model=SendMessageResponse)
async def send_message(payload: SendMessageRequest):
    """
    Kirim pesan ke pengguna atau channel Telegram melalui Telegram Gateway.
    """
    try:
        sent = await bot.send_message(
            chat_id=payload.chat_id,
            text=payload.text,
            parse_mode=payload.parse_mode
        )
        logger.info(f"Pesan berhasil dikirim ke chat_id {payload.chat_id}, message_id: {sent.message_id}")
        return SendMessageResponse(
            ok=True,
            message_id=sent.message_id,
            chat_id=payload.chat_id,
            detail="Pesan berhasil dikirim"
        )
    except Exception as e:
        logger.error(f"Gagal mengirim pesan ke chat_id {payload.chat_id}: {e}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Gagal mengirim pesan Telegram: {str(e)}"
        )
