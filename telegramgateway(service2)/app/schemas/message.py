from pydantic import BaseModel, Field
from typing import Union, Optional

class SendMessageRequest(BaseModel):
    chat_id: Union[int, str] = Field(
        ...,
        description="Target Telegram chat ID (misal: 123456789) atau @channelusername"
    )
    text: str = Field(
        ...,
        description="Teks pesan yang ingin dikirim"
    )
    parse_mode: Optional[str] = Field(
        "HTML",
        description="Mode formatting: 'HTML', 'MarkdownV2', 'Markdown', atau null"
    )

class SendMessageResponse(BaseModel):
    ok: bool
    message_id: Optional[int] = None
    chat_id: Union[int, str]
    detail: Optional[str] = None
