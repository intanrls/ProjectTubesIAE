<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessInventoryMessage; // Pastikan import ini ada

class MessageController extends Controller
{
    public function publish(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'message' => 'required|string',
            'routing_key' => 'required|string'
        ]);

        // 2. Dispatch ke Job RabbitMQ
        ProcessInventoryMessage::dispatch($request->input('message'));

        // 3. Respon sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dikirim ke antrean'
        ], 200);
    }
}