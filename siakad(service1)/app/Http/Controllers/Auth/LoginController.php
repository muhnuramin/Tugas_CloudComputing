<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramLoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input login
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Proses autentikasi
        if (Auth::attempt($credentials)) {

            // Regenerasi session untuk keamanan
            $request->session()->regenerate();

            // Mengambil data user yang berhasil login
            $user = Auth::user();

            // Mengirim notifikasi melalui Laravel Queue
            SendTelegramLoginNotification::dispatch([
                'name' => $user->name,
                'email' => $user->email,
                'message' => 'Pengguna berhasil login ke LMS',
            ]);

            // Langsung arahkan user ke dashboard
            return redirect()->intended('/dashboard');
        }

        // Jika login gagal
        return back()->withErrors([
            'email' => 'Email atau password yang dimasukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
