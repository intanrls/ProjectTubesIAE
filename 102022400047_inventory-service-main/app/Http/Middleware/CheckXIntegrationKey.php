<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckXIntegrationKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Jika header pengaman tidak disertakan
        if (!$request->hasHeader('X-IAE-KEY')) {
            return response()->json([
                "status" => "error",
                "message" => "Header X-IAE-KEY tidak ditemukan",
                "errors" => null
            ], 401);
        }

        // 2. Jika header ada tapi NIM yang dimasukkan salah
        if ($request->header('X-IAE-KEY') !== '102022400047') {
            return response()->json([
                "status" => "error",
                "message" => "Kunci integrasi NIM salah atau tidak terdaftar",
                "errors" => null
            ], 403);
        }

        return $next($request);
    }
}