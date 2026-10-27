from aiogram import Router, types
from aiogram.filters import Command

router = Router()


@router.message(Command("start", "id", "chatid", "myid"))
async def start_handler(message: types.Message):
    chat_id = message.chat.id
    user_name = message.from_user.full_name if message.from_user else "Pengguna"
    username = f"@{message.from_user.username}" if message.from_user and message.from_user.username else "-"

    text = (
        f"👋 Halo <b>{user_name}</b>!\n\n"
        f"Selamat datang di <b>Telegram Gateway SIAKAD</b>.\n\n"
        f"🆔 <b>Chat ID Anda:</b>\n"
        f"<code>{chat_id}</code> (klik untuk menyalin)\n\n"
        f"📌 <b>Langkah Integrasi SIAKAD:</b>\n"
        f"1. Salin Chat ID di atas.\n"
        f"2. Buka sistem <b>SIAKAD</b> dan masuk ke menu <b>Profil</b> (atau minta Admin mendaftarkan Chat ID Anda).\n"
        f"3. Tempelkan Chat ID pada kolom <b>Telegram Chat ID</b> lalu simpan.\n\n"
        f"✅ Setelah tersimpan, setiap kali Anda login ke akun SIAKAD, Anda akan menerima pesan notifikasi keamanan secara instan di sini."
    )
    await message.answer(text, parse_mode="HTML")


@router.message(Command("help"))
async def help_handler(message: types.Message):
    help_text = (
        "ℹ️ <b>Bantuan Telegram Gateway SIAKAD</b>\n\n"
        "Perintah yang tersedia:\n"
        "• /start - Menampilkan info bot dan Chat ID Anda\n"
        "• /id atau /myid - Menampilkan Chat ID Anda untuk SIAKAD\n"
        "• /help - Menampilkan pesan bantuan ini\n\n"
        "Layanan ini digunakan sebagai Service 2 (Gateway) untuk mengirimkan notifikasi keamanan login dari sistem SIAKAD."
    )
    await message.answer(help_text, parse_mode="HTML")
