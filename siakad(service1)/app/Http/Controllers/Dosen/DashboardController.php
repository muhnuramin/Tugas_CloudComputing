<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Nilai;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Services\CacheService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $dosen = $user->dosen;
        
        if (!$dosen) {
            abort(403, 'Unauthorized');
        }

        $activeTA = app(CacheService::class)->getActiveTahunAkademik();

        // Get kelas yang diampu (filter by active tahun akademik if available)
        $kelasQuery = Kelas::where('dosen_id', $dosen->id)
            ->with(['mataKuliah', 'jadwal'])
            ->withCount('krsDetail');
        
        if ($activeTA) {
            $kelasQuery->where(function($q) use ($activeTA) {
                $q->where('tahun_akademik_id', $activeTA->id)
                  ->orWhereNull('tahun_akademik_id'); // Include legacy kelas without TA
            });
        }
        
        $kelasList = $kelasQuery->get();
        $kelasIds = $kelasList->pluck('id');

        // Stats
        $totalKelas = $kelasList->count();
        $totalMahasiswa = (int) $kelasList->sum('krs_detail_count');
        
        // Get total nilai yang sudah diinput
        $nilaiDiinput = Nilai::whereIn('kelas_id', $kelasIds)
            ->whereNotNull('nilai_angka')
            ->count();
        $totalNilai = $totalMahasiswa;
        $persentaseNilai = $totalNilai > 0 ? round(($nilaiDiinput / $totalNilai) * 100) : 0;

        // Presensi stats
        $pertemuanIds = Pertemuan::whereHas('jadwalKuliah', fn($q) => $q->whereIn('kelas_id', $kelasIds))->pluck('id');
        $totalPertemuan = $pertemuanIds->count();
        
        $presensiStats = Presensi::whereIn('pertemuan_id', $pertemuanIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Mahasiswa bimbingan
        $mahasiswaBimbingan = $dosen->mahasiswaBimbingan()->select('id', 'dosen_pa_id')->get();
        $pendingKrs = $mahasiswaBimbingan->isNotEmpty() 
            ? Krs::whereIn('mahasiswa_id', $mahasiswaBimbingan->pluck('id'))
                ->where('status', 'pending')
                ->count()
            : 0;

        // Recent activities (recent nilai input)
        $recentNilai = Nilai::whereIn('kelas_id', $kelasIds)
            ->whereNotNull('nilai_angka')
            ->with(['kelas.mataKuliah', 'mahasiswa.user'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Kelas dengan jadwal hari ini
        $hariMapping = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        $hariIni = $hariMapping[now()->format('l')] ?? now()->format('l');
        
        $kelasHariIni = $kelasList->filter(function ($kelas) use ($hariIni) {
            return $kelas->jadwal->contains('hari', $hariIni);
        });

        return view('dosen.dashboard.index', compact(
            'dosen', 'activeTA', 'kelasList', 'totalKelas', 'totalMahasiswa',
            'nilaiDiinput', 'totalNilai', 'persentaseNilai', 'totalPertemuan',
            'presensiStats', 'mahasiswaBimbingan', 'pendingKrs', 'recentNilai',
            'kelasHariIni', 'hariIni'
        ));
    }
}
