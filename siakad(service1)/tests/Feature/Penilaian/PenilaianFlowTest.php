<?php

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Notification;
use App\Models\Prodi;
use App\Models\User;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('dosen can view penilaian index page', function () {
    $user = User::factory()->create(['role' => 'dosen']);
    $prodi = Prodi::factory()->create();
    Dosen::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dosen.penilaian.index'));
    
    $response->assertStatus(200);
});

test('dosen cannot access admin routes', function () {
    $user = User::factory()->create(['role' => 'dosen']);
    Dosen::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));
    
    $response->assertStatus(403);
});

test('mahasiswa cannot access penilaian routes', function () {
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::factory()->create();
    \App\Models\Mahasiswa::factory()->create([
        'user_id' => $user->id,
        'prodi_id' => $prodi->id,
    ]);

    $response = $this->actingAs($user)->get(route('dosen.penilaian.index'));
    
    $response->assertStatus(403);
});

test('bulk grade input creates an in-app notification for each student', function () {
    Queue::fake();
    Http::fake();
    $studentUser = User::factory()->create([
        'role' => 'mahasiswa',
        'telegram_chat_id' => '123456789',
    ]);
    $mahasiswa = Mahasiswa::factory()->create(['user_id' => $studentUser->id]);
    $dosenUser = User::factory()->create(['role' => 'dosen']);
    $dosen = Dosen::create([
        'user_id' => $dosenUser->id,
        'nidn' => '1234567890',
        'prodi_id' => $mahasiswa->prodi_id,
    ]);
    $mataKuliah = MataKuliah::create([
        'kode_mk' => 'TEST-101',
        'nama_mk' => 'Pemrograman Dasar',
        'sks' => 3,
        'semester' => 1,
    ]);
    $kelas = Kelas::create([
        'mata_kuliah_id' => $mataKuliah->id,
        'dosen_id' => $dosen->id,
        'nama_kelas' => 'A',
        'kapasitas' => 30,
    ]);

    $updated = app(PenilaianService::class)->bulkInputNilai($kelas->id, [
        $mahasiswa->id => 90,
    ]);

    expect($updated)->toBe(1);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $studentUser->id,
        'type' => Notification::TYPE_NILAI_UPDATED,
    ]);
    Http::assertNothingSent();
});
