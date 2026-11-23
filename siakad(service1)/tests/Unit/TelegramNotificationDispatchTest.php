<?php

use App\Jobs\SendTelegramNotification;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TelegramGatewayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

test('login telegram notification is queued for the users chat id', function () {
    Queue::fake();
    $user = new User([
        'name' => 'Test User',
        'role' => 'mahasiswa',
        'telegram_chat_id' => '123456789',
    ]);

    app(TelegramGatewayService::class)->queueLoginNotification($user);

    Queue::assertPushed(SendTelegramNotification::class, function ($job) {
        return $job->chatId === '123456789' &&
            str_contains($job->message, 'Notifikasi Login SIAKAD');
    });
});

test('application notifications are queued after they are saved', function () {
    Queue::fake();
    config(['services.telegram.notification_chat_id' => null]);
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type');
        $table->string('title');
        $table->text('message');
        $table->json('data')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    try {
        $user = (new User)->forceFill([
            'id' => 7,
            'telegram_chat_id' => '987654321',
        ]);
        $notification = app(NotificationService::class)->send(
            $user,
            'jadwal_change',
            'Perubahan jadwal',
            'Jadwal kelas diperbarui.',
        );

        expect($notification->exists)->toBeTrue();
        Queue::assertPushed(SendTelegramNotification::class, function ($job) {
            return $job->chatId === '987654321' &&
                str_contains($job->message, 'Perubahan jadwal') &&
                str_contains($job->message, 'Jadwal kelas diperbarui.');
        });
    } finally {
        Schema::dropIfExists('notifications');
    }
});

test('application notifications are queued to the configured group without a personal chat id', function () {
    Queue::fake();
    config(['services.telegram.notification_chat_id' => '-1001234567890']);
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type');
        $table->string('title');
        $table->text('message');
        $table->json('data')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    try {
        $user = (new User)->forceFill([
            'id' => 8,
            'name' => 'Test Recipient',
            'telegram_chat_id' => null,
        ]);
        app(NotificationService::class)->send(
            $user,
            'nilai_updated',
            'Nilai Diperbarui',
            'Nilai Anda telah diperbarui.',
        );

        Queue::assertPushed(SendTelegramNotification::class, function ($job) {
            return $job->chatId === '-1001234567890' &&
                str_contains($job->message, 'Penerima: Test Recipient') &&
                str_contains($job->message, 'Nilai Anda telah diperbarui.');
        });
    } finally {
        Schema::dropIfExists('notifications');
    }
});
