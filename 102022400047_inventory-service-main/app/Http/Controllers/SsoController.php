<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function handleCallback(Request $request)
    {
        // 1. Ambil data input. 
        // Menggunakan ?? (null coalescing operator) untuk menerima 'password' atau 'api_key'
        $email = $request->input('email');
        $secret = $request->input('password') ?? $request->input('api_key');

        if (!$email || !$secret) {
            return response()->json(['message' => 'Email dan Password/API Key diperlukan'], 400);
        }

        try {
            // 2. Request ke IAE SSO Server
            // ->withoutVerifying() tetap digunakan untuk mengatasi SSL
            $response = Http::timeout(10)
                ->withoutVerifying() 
                ->post('https://iae-sso.virtualfri.id/api/v1/auth/token', [
                    'email'    => $email,
                    'password' => $secret // Mengirimkan nilai secret (apapun itu) sebagai password ke server SSO
                ]);

            // 3. Cek apakah request berhasil
            if ($response->successful()) {
                $data = $response->json();
                
                // Mengambil token dari berbagai kemungkinan key (access_token atau token)
                $token = $data['access_token'] ?? ($data['token'] ?? null);
                
                return response()->json([
                    'message' => 'Login SSO IAE Berhasil',
                    'token'   => $token
                ]);
            }

            // 4. Jika server SSO menolak (401, 403, dll)
            Log::warning('SSO IAE Rejected: ' . $response->body());
            
            return response()->json([
                'message' => 'Gagal login ke IAE (Server SSO menolak kredensial)', 
                'details' => $response->json() // Menampilkan alasan asli dari server dosen
            ], $response->status());

        } catch (\Exception $e) {
            // 5. Jika koneksi error
            Log::error('SSO IAE Connection Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server IAE tidak merespon', 
                'error' => $e->getMessage()
            ], 503);
        }
    }
}