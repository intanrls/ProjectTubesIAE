<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Services\SoapAuditService; // Import Service
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInventoryMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param SoapAuditService $soapService // Laravel otomatis akan mengisi ini
     */
    public function handle(SoapAuditService $soapService)
    {
        try {
            Log::info('--- Job ProcessInventoryMessage Dimulai ---');

            // 1. Panggil SOAP Service (Modul 2)
            // Mengirim data ke dosen dengan TeamID = TEAM-03
            $receiptNumber = $soapService->sendAudit('TEAM-03', 'InventoryCreated', $this->data);
            
            Log::info("SOAP Audit berhasil. Receipt: " . $receiptNumber);

            // 2. Simpan ke Database (dengan Receipt Number)
            AuditLog::create([
                'inventory_id'   => $this->data['inventory_id'],
                'quantity'       => $this->data['quantity'],
                'receipt_number' => $receiptNumber, // Simpan bukti audit
                'status'         => 'processed'
            ]);

            Log::info("Data berhasil disimpan ke database dengan Receipt Number.");
            
            // 3. Tambahan: Jika perlu integrasi RabbitMQ (Modul 3), 
            // Anda bisa memanggil function publisher di sini.
            $iaeCloud = app(\App\Services\IaeCloudService::class);
            
            $rabbitPayload = [
                'event' => 'InventoryStockUpdate',
                'team_id' => 'TEAM-03',
                'inventory_id' => (int) ($this->data['inventory_id'] ?? 1),
                'quantity' => (int) ($this->data['quantity'] ?? 50),
                'receipt' => $receiptNumber,
                'timestamp' => now()->toIso8601String()
            ];

            $iaeCloud->publishRabbitMq('inventory.stock.updated', $rabbitPayload);
            
            Log::info('--- Job Berhasil Diproses ---');

        } catch (\Exception $e) {
            Log::error("Job Gagal: " . $e->getMessage());
            throw $e;
        }
    }
}