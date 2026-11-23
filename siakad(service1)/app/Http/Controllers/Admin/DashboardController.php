<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, CacheService $cacheService)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $fakultasId = $user->fakultas_id;
        $cacheKey = 'stats.admin_dashboard.' . ($isSuperAdmin ? 'superadmin' : 'fakultas_' . $fakultasId);

        $dashboardData = Cache::remember($cacheKey, CacheService::TTL_SHORT, function () use ($isSuperAdmin, $fakultasId) {
            // For admin_fakultas, get their fakultas info
            $fakultas = null;
            if (!$isSuperAdmin && $fakultasId) {
                $fakultas = Fakultas::find($fakultasId);
            }

            // Build scoped queries
            $prodiQuery = Prodi::query();
            $mahasiswaQuery = Mahasiswa::query();
            $dosenQuery = Dosen::query();
            $mataKuliahQuery = MataKuliah::query();
            $kelasQuery = Kelas::query();
            $nilaiQuery = Nilai::query();

            if (!$isSuperAdmin && $fakultasId) {
                $prodiQuery->where('fakultas_id', $fakultasId);
                $mahasiswaQuery->whereHas('prodi', fn($q) => $q->where('fakultas_id', $fakultasId));
                $dosenQuery->whereHas('prodi', fn($q) => $q->where('fakultas_id', $fakultasId));
                $mataKuliahQuery->whereHas('prodi', fn($q) => $q->where('fakultas_id', $fakultasId));
                $kelasQuery->whereHas('dosen.prodi', fn($q) => $q->where('fakultas_id', $fakultasId));
                $nilaiQuery->whereHas('mahasiswa.prodi', fn($q) => $q->where('fakultas_id', $fakultasId));
            }

            // Basic counts (scoped)
            $stats = [
                'prodi' => (clone $prodiQuery)->count(),
                'mahasiswa' => (clone $mahasiswaQuery)->count(),
                'dosen' => (clone $dosenQuery)->count(),
                'mata_kuliah' => (clone $mataKuliahQuery)->count(),
                'kelas' => (clone $kelasQuery)->count(),
            ];

            // Only show fakultas count for superadmin
            if ($isSuperAdmin) {
                $stats['fakultas'] = Fakultas::count();
            }

            // Grade distribution (scoped)
            $gradeDistribution = (clone $nilaiQuery)
                ->selectRaw('nilai_huruf, COUNT(*) as count')
                ->groupBy('nilai_huruf')
                ->pluck('count', 'nilai_huruf')
                ->toArray();

            // Per-prodi student count (scoped)
            $prodiStatsQuery = Prodi::withCount('mahasiswa');
            if (!$isSuperAdmin && $fakultasId) {
                $prodiStatsQuery->where('fakultas_id', $fakultasId);
            }
            $prodiStats = $prodiStatsQuery
                ->orderBy('mahasiswa_count', 'desc')
                ->take(10)
                ->get();

            // Per-prodi dosen count (scoped) - NEW for admin_fakultas
            $dosenPerProdiQuery = Prodi::withCount('dosen');
            if (!$isSuperAdmin && $fakultasId) {
                $dosenPerProdiQuery->where('fakultas_id', $fakultasId);
            }
            $dosenPerProdi = $dosenPerProdiQuery
                ->orderBy('dosen_count', 'desc')
                ->take(10)
                ->get();

            return compact('stats', 'gradeDistribution', 'prodiStats', 'dosenPerProdi', 'fakultas');
        });

        // Active academic year (cached)
        $activeYear = $cacheService->getActiveTahunAkademik();

        return view('admin.dashboard.index', array_merge($dashboardData, compact('activeYear', 'isSuperAdmin')));
    }
}

