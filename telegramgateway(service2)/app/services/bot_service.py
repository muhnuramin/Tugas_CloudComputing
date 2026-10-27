from aiogram.client.default import DefaultBotProperties
from aiogram import Bot, Dispatcher
from aiogram.types import BotCommand
from app.config.config import settings
from app.bot.handlers import start, miniapp
bot = Bot(
    token=settings.TELEGRAM_BOT_TOKEN,
    default=DefaultBotProperties(parse_mode="HTML")
)

dp = Dispatcher()
# dp.include_router(default.router)
dp.include_router(start.router)
dp.include_router(miniapp.router)


async def set_bot_commands():
    commands = [
        BotCommand(command="start", description="Mulai bot & lihat Chat ID"),
        BotCommand(command="id", description="Cek Chat ID Telegram Anda"),
        BotCommand(command="help", description="Petunjuk penggunaan"),
    ]
    await bot.set_my_commands(commands)
