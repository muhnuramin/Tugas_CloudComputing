from aiogram import Router, types

router = Router()

@router.message()
async def default_handler(message: types.Message):
    await message.answer("من این پیام را متوجه نشدم، لطفاً یکی از گزینه‌های منو را انتخاب کنید.")
