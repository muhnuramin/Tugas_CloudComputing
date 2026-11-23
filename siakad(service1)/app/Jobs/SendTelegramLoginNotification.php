<?php

namespace App\Jobs;

use App\Services\TelegramGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use RuntimeException;

class SendTelegramLoginNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 30;

    public function __construct(
        public array $userData
    ) {}

    public function handle(TelegramGatewayService $telegramGateway): void
    {
        $chatId = config('services.telegram.notification_chat_id');

        if (! $chatId) {
            throw new InvalidArgumentException('Telegram notification chat ID must be configured.');
        }

        $message = implode("\n", [
            'Notifikasi login LMS',
            'Nama: '.($this->userData['name'] ?? '-'),
            'Email: '.($this->userData['email'] ?? '-'),
            'Pesan: '.($this->userData['message'] ?? '-'),
        ]);

        if (! $telegramGateway->sendMessage($chatId, $message, null)) {
            throw new RuntimeException('Telegram login notification could not be delivered.');
        }
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }
}
