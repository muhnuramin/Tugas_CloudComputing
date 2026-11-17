<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\TelegramGatewayService;
use App\Jobs\SendTelegramNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendTelegramLoginNotification;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertViewIs('auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('successful login queues a Telegram notification for the configured recipient', function () {
    Queue::fake();
    config(['services.telegram.notification_chat_id' => '-5312678501']);

    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    Queue::assertPushed(SendTelegramLoginNotification::class, function ($job) use ($user) {
        return $job->userData['email'] === $user->email;
    });
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors();
});

test('users can not authenticate with invalid email', function () {
    $response = $this->post('/login', [
        'email' => 'nonexistent@test.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

test('login requires email', function () {
    $response = $this->post('/login', [
        'email' => '',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('login requires password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticated();

    $response = $this->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('authenticated users are redirected from login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('dashboard'));
});

test('users can view registration page', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertViewIs('auth.register');
});

test('users can register with valid data', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
    
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

test('users cannot register with existing email', function () {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('registration requires password confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('registration requires minimum password length', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('login does not wait for telegram delivery', function () {
    Queue::fake();
    \Illuminate\Support\Facades\Http::fake([
        '*/send-message' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
    ]);
    config(['services.telegram.notification_chat_id' => null]);

    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'telegram_chat_id' => '123456789',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    \Illuminate\Support\Facades\Http::assertNothingSent();
});

test('telegram notification job sends its message through the configured gateway', function () {
    \Illuminate\Support\Facades\Http::fake([
        '*/send-message' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
    ]);

    $job = new SendTelegramNotification('123456789', 'KRS Disetujui', null);
    $job->handle(app(TelegramGatewayService::class));

    \Illuminate\Support\Facades\Http::assertSent(function ($request) {
        return str_contains($request->url(), '/send-message') &&
            $request['chat_id'] === '123456789' &&
            $request['text'] === 'KRS Disetujui' &&
            $request['parse_mode'] === null;
    });
});

test('application notifications persist without synchronously contacting telegram', function () {
    Queue::fake();
    \Illuminate\Support\Facades\Http::fake();
    $user = User::factory()->create(['telegram_chat_id' => '123456789']);

    $notification = app(NotificationService::class)->send(
        $user,
        'jadwal_change',
        'Perubahan jadwal',
        'Jadwal kelas diperbarui.',
    );

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'user_id' => $user->id,
    ]);
    \Illuminate\Support\Facades\Http::assertNothingSent();
});

test('login does not send telegram notification when user has no telegram_chat_id', function () {
    \Illuminate\Support\Facades\Http::fake();
    config(['services.telegram.notification_chat_id' => null]);

    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'telegram_chat_id' => null,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    \Illuminate\Support\Facades\Http::assertNothingSent();
});