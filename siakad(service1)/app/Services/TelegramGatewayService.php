<?php

namespace App\Services;

use App\Jobs\SendTelegramNotification;
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
    public function queueLoginNotification(User $user, ?Request $request = null): void
    {
        if (empty($user->telegram_chat_id)) {
            return;
        }

        $now = now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s');
        $ip = $request ? $request->ip() : request()->ip();
        $userAgent = $request ? $request->userAgent() : request()->userAgent();
        $device = $this->parseUserAgent($userAgent);

        $roleName = ucfirst(str_replace('_', ' ', $user->role ?? 'User'));

        $message = "🔔 <b>Notifikasi Login SIAKAD</b>\n\n"
            .'Halo, <b>'.htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8')."</b>! 👋\n"
            ."Akun Anda baru saja login ke Sistem Informasi Akademik (SIAKAD).\n\n"
            ."📋 <b>Detail Sesi:</b>\n"
            ."• <b>Waktu:</b> {$now} WIB\n"
            ."• <b>Role:</b> {$roleName}\n"
            ."• <b>IP Address:</b> <code>{$ip}</code>\n"
            ."• <b>Perangkat:</b> {$device}\n\n"
            .'⚠️ <i>Jika ini bukan Anda, segera ubah kata sandi akun Anda atau hubungi administrator.</i>';

        SendTelegramNotification::dispatch($user->telegram_chat_id, $message, 'HTML')->afterCommit();
    }

    /**
     * Kirim langsung ke Bot API bila token tersedia; gunakan Gateway sebagai fallback.
     */
    public function sendMessage(string|int $chatId, string $text, ?string $parseMode = 'HTML'): bool
    {
        $botToken = config('services.telegram.bot_token');
        $sendDirectly = is_string($botToken) && $botToken !== '';
        $endpoint = $sendDirectly
            ? "https://api.telegram.org/bot{$botToken}/sendMessage"
            : "{$this->baseUrl}/send-message";
        $payload = array_filter([
            'chat_id' => (string) $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ], fn ($value) => $value !== null);

        try {
            $response = Http::timeout($this->timeout)->post($endpoint, $payload);

            if ($response->successful() && (! $sendDirectly || $response->json('ok') === true)) {
                Log::info("Notifikasi Telegram berhasil dikirim ke chat_id: {$chatId}");

                return true;
            }

            if ($sendDirectly) {
                Log::warning('Telegram Bot API mengembalikan kegagalan.', [
                    'status' => $response->status(),
                    'description' => $response->json('description'),
                ]);

                return false;
            }

            Log::warning("Telegram Gateway mengembalikan status [{$response->status()}]: {$response->body()}");

            return false;
        } catch (\Throwable $e) {
            $destination = $sendDirectly ? 'Telegram Bot API' : "Telegram Gateway ({$this->baseUrl})";
            $error = $sendDirectly
                ? str_replace((string) $botToken, '[REDACTED]', $e->getMessage())
                : $e->getMessage();

            Log::error("Gagal terhubung ke {$destination}: {$error}");

            return false;
        }
    }

    /**
     * Parsing sederhana user agent untuk deteksi nama browser dan sistem operasi.
     */
    protected function parseUserAgent(?string $userAgent): string
    {
        if (! $userAgent) {
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
