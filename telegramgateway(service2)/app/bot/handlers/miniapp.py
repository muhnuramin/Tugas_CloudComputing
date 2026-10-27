from aiogram import Router, types

router = Router()

WEBAPP_URL = "https://example.com"


@router.message(lambda m: m.text is not None and not m.text.startswith("/"))
async def miniapp_handler(message: types.Message):
    text = message.text.strip()
    if text == "🚀 خرید توکن":
        kb = types.InlineKeyboardMarkup(
            inline_keyboard=[
                [
                    types.InlineKeyboardButton(
                        text="🚀 ورود به مینی اپ",
                        web_app=types.WebAppInfo(url="https://example.com"),
                    )
                ]
            ]
        )
        await message.answer(
            "سلام 👋 برای ورود به مینی اپ روی دکمه زیر بزن:", reply_markup=kb
        )
        return

    if text == "💰 کیف پول":
        kb = types.InlineKeyboardMarkup(
            inline_keyboard=[
                [
                    types.InlineKeyboardButton(
                        text="💰 ورود به کیف پول",
                        web_app=types.WebAppInfo(url="https://example.com"),
                    )
                ]
            ]
        )
        await message.answer(
            "👋 برای ورود به کیف پول روی دکمه زیر بزن:", reply_markup=kb
        )
        return

    if text == "📞 ارتباط با ما":
        kb = types.InlineKeyboardMarkup(
            inline_keyboard=[
                [
                    types.InlineKeyboardButton(
                        text="📞  راه های ارتباط با ما",
                        web_app=types.WebAppInfo(url="https://www.example.com/"),
                    )
                ]
            ]
        )
        await message.answer("👋 پشتیبانی مجموعه ما :", reply_markup=kb)
        return
