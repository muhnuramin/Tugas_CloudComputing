<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramGatewayService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.telegram_gateway.base_url', env('TELEGRAM_GATEWAY_URL', 'http://localhost:8000')), '/');
        $this->timeout = (int) config('services.telegram_gateway.timeout', 5);
    }

    /**
     * Kirim notifikasi login ke Telegram milik user melalui Service 2 (Telegram Gateway).
     */
    public function sendLoginNotification(User $user, ?Request $request = null): bool
    {
        if (empty($user->telegram_chat_id)) {
            return false;
        }

        $now = now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s');
        $ip = $request ? $request->ip() : request()->ip();
        $userAgent = $request ? $request->userAgent() : request()->userAgent();
        $device = $this->parseUserAgent($userAgent);

        $roleName = ucfirst(str_replace('_', ' ', $user->role ?? 'User'));

        $message = "🔔 <b>Notifikasi Login SIAKAD</b>\n\n"
            . "Halo, <b>" . htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') . "</b>! 👋\n"
            . "Akun Anda baru saja login ke Sistem Informasi Akademik (SIAKAD).\n\n"
            . "📋 <b>Detail Sesi:</b>\n"
            . "• <b>Waktu:</b> {$now} WIB\n"
            . "• <b>Role:</b> {$roleName}\n"
            . "• <b>IP Address:</b> <code>{$ip}</code>\n"
            . "• <b>Perangkat:</b> {$device}\n\n"
            . "⚠️ <i>Jika ini bukan Anda, segera ubah kata sandi akun Anda atau hubungi administrator.</i>";

        return $this->sendMessage($user->telegram_chat_id, $message, 'HTML');
    }

    /**
     * Kirim pesan teks ke Telegram melalui endpoint Service 2 (/send-message).
     */
    public function sendMessage(string|int $chatId, string $text, string $parseMode = 'HTML'): bool
    {
        try {
            $endpoint = "{$this->baseUrl}/send-message";

            $response = Http::timeout($this->timeout)->post($endpoint, [
                'chat_id' => (string) $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
            ]);

            if ($response->successful()) {
                Log::info("Notifikasi Telegram berhasil dikirim ke chat_id: {$chatId}");
                return true;
            }

            Log::warning("Telegram Gateway mengembalikan status [{$response->status()}]: {$response->body()}");
            return false;
        } catch (\Throwable $e) {
            // Mencegah kegagalan gateway mempengaruhi alur login user
            Log::error("Gagal terhubung ke Telegram Gateway ({$this->baseUrl}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parsing sederhana user agent untuk deteksi nama browser dan sistem operasi.
     */
    protected function parseUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Tidak Diketahui';
        }

        if (str_contains($userAgent, 'Firefox')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($userAgent, 'Edg')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($userAgent, 'Chrome')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Apple Safari';
        } elseif (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            $browser = 'Opera';
        } else {
            $browser = 'Web Browser';
        }

        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } else {
            $os = '';
        }

        return $os ? "{$browser} ({$os})" : $browser;
    }
}
