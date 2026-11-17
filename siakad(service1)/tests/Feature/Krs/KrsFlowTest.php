<?php

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Notification;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Create active tahun akademik
    TahunAkademik::factory()->create(['is_active' => true]);
});

test('mahasiswa can view krs page', function () {
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::factory()->create();
    Mahasiswa::factory()->create([
        'user_id' => $user->id,
        'prodi_id' => $prodi->id,
    ]);

    $response = $this->actingAs($user)->get(route('mahasiswa.krs.index'));
    
    $response->assertStatus(200);
});

test('mahasiswa cannot access admin routes', function () {
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::factory()->create();
    Mahasiswa::factory()->create([
        'user_id' => $user->id,
        'prodi_id' => $prodi->id,
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));
    
    $response->assertStatus(403);
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('mahasiswa.krs.index'));
    
    $response->assertRedirect(route('login'));
});

test('approved krs creates an in-app notification for its student', function () {
    Queue::fake();
    Http::fake();
    $user = User::factory()->create([
        'role' => 'mahasiswa',
        'telegram_chat_id' => '123456789',
    ]);
    $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id]);
    $tahunAkademik = TahunAkademik::firstOrFail();
    $krs = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'tahun_akademik_id' => $tahunAkademik->id,
        'status' => 'approved',
    ]);

    app(NotificationService::class)->notifyKrsStatus($krs);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => Notification::TYPE_KRS_APPROVED,
    ]);
    Http::assertNothingSent();
});
