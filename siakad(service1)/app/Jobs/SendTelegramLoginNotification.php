<?php

namespace App\Jobs;

use InvalidArgumentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendTelegramLoginNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 30;

    public function __construct(
        public array $userData
    ) {}

    public function handle(): void
    {
        $url = config('services.telegram.url');
        $chatId = config('services.telegram.notification_chat_id');

        if (!$url || !$chatId) {
            throw new InvalidArgumentException('Telegram gateway URL and notification chat ID must be configured.');
        }

        Http::timeout(10)
            ->post(rtrim($url, '/') . '/send-message', [
                'chat_id' => $chatId,
                'text' => implode("\n", [
                    'Notifikasi login LMS',
                    'Nama: ' . ($this->userData['name'] ?? '-'),
                    'Email: ' . ($this->userData['email'] ?? '-'),
                    'Pesan: ' . ($this->userData['message'] ?? '-'),
                ]),
                'parse_mode' => null,
            ])
            ->throw();
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }
}