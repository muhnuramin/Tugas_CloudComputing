<?php

namespace App\Jobs;

use App\Services\TelegramGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public string $chatId,
        public string $message,
        public ?string $parseMode = null,
    ) {}

    public function handle(TelegramGatewayService $telegramGateway): void
    {
        if (!$telegramGateway->sendMessage($this->chatId, $this->message, $this->parseMode)) {
            throw new RuntimeException("Telegram notification failed for chat ID {$this->chatId}.");
        }
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Telegram notification permanently failed.', [
            'chat_id' => $this->chatId,
            'error' => $exception->getMessage(),
        ]);
    }
}
