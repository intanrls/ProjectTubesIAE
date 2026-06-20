<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessInventoryMessage;
use App\Models\Inventory;
use App\Services\SoapAuditService;
use App\Http\Resources\InventoryResource; // Pastikan import ini ada

class InventoryController extends Controller
{
    protected $soapService;

    public function __construct(SoapAuditService $soapService)
    {
        $this->soapService = $soapService;
    }

    /**
     * @OA\Get(
     * path="/api/v1/inventories",
     * summary="Lihat daftar barang",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Berhasil mengambil data"),
     * @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $items = Inventory::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar barang di gudang',
            'data' => InventoryResource::collection($items)
        ], 200);
    }

    /**
     * @OA\Get(
     * path="/api/v1/inventories/{id}",
     * summary="Lihat detail barang",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Data ditemukan"),
     * @OA\Response(response=404, description="Barang tidak ditemukan")
     * )
     */
    public function show($id)
    {
        try {
            // Menggunakan findOrFail untuk otomatis melempar Exception jika data tidak ada
            $item = Inventory::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Detail ketersediaan stok barang ditemukan',
                'data' => new InventoryResource($item) 
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang tidak ditemukan'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/v1/inventories/qc",
     * summary="Memproses barang order sekaligus mencatat hasil QC",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *   required=true,
     *   @OA\JsonContent(
     *     required={"order_id","qc_status"},
     *     @OA\Property(property="order_id", type="string", example="ORD-001", description="ID Order dari Order Service (format: ORD-XXX)"),
     *     @OA\Property(property="qc_status", type="string", example="PASSED", description="Status QC: PASSED atau FAILED"),
     *     @OA\Property(property="notes", type="string", example="Barang lolos inspeksi, kondisi mulus sesuai pesanan")
     *   )
     * ),
     * @OA\Response(response=201, description="QC Berhasil dicatat"),
     * @OA\Response(response=400, description="Request tidak valid"),
     * @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function storeQC(Request $request)
    {
        $request->validate([
            'order_id'  => 'required',
            'qc_status' => 'required|string|in:PASSED,FAILED,PASS,FAIL',
            'notes'     => 'nullable|string',
        ]);

        // Normalisasi order_id: jika dikirim angka biasa (misal "1" atau 1),
        // ubah ke format ORD-001 agar selaras dengan Order Service
        $rawOrderId = $request->input('order_id');
        if (is_numeric($rawOrderId)) {
            $orderId = 'ORD-' . str_pad((int) $rawOrderId, 3, '0', STR_PAD_LEFT);
        } else {
            $orderId = strtoupper(trim($rawOrderId));
        }

        $qcStatus = strtoupper($request->input('qc_status'));
        $notes    = $request->input('notes', '-');

        // Normalisasi status: PASS → PASSED, FAIL → FAILED
        if ($qcStatus === 'PASS') $qcStatus = 'PASSED';
        if ($qcStatus === 'FAIL') $qcStatus = 'FAILED';

        // Kirim SOAP Audit ke server dosen
        $iaeCloud      = app(\App\Services\IaeCloudService::class);
        $receiptNumber = null;

        $soapData = [
            'order_id'     => $orderId,
            'qc_status'    => $qcStatus,
            'notes'        => $notes,
            'processed_at' => now()->toIso8601String(),
        ];

        try {
            $receiptNumber = $iaeCloud->sendSoapAudit('QCCompleted', $soapData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SOAP QC Audit gagal: ' . $e->getMessage());
        }

        // Publish event ke RabbitMQ: inventory.qc.completed
        // Service lain (Expedition) yang kemudian merespons event ini secara mandiri
        $rabbitPayload = [
            'team_id'  => config('services.iae_cloud.team_id', 'TEAM-03'),
            'service'  => 'Inventory-Service',
            'activity' => 'QCCompleted',
            'event'    => 'inventory.qc.completed',
            'data'     => [
                'order_id'       => $orderId,
                'qc_status'      => $qcStatus,
                'notes'          => $notes,
                'receipt_number' => $receiptNumber,
                'processed_at'   => now()->toIso8601String(),
            ],
        ];

        $rabbitStatus = false;
        try {
            $rabbitStatus = $iaeCloud->publishRabbitMq('inventory.qc.completed', $rabbitPayload);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RabbitMQ QC publish gagal: ' . $e->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Hasil QC berhasil dicatat. Notifikasi telah dikirim ke sistem.',
            'receipt' => [
                'order_id'     => $orderId,
                'qc_status'    => $qcStatus,
                'notes'        => $notes,
                'processed_at' => now()->toDateTimeString(),
            ],
            'integration' => [
                'soap_audit' => [
                    'status'         => $receiptNumber ? 'success' : 'failed',
                    'receipt_number' => $receiptNumber,
                ],
                'rabbitmq' => [
                    'status' => $rabbitStatus ? 'success' : 'failed',
                    'event'  => 'inventory.qc.completed',
                ],
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/v1/inventories",
     * summary="Tambah ke antrean (Queue)",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=202, description="Data diproses di antrean")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        ProcessInventoryMessage::dispatch($data);

        return response()->json([
            'message' => 'Data sedang diproses di antrean!',
        ], 202);
    }

    /**
     * @OA\Post(
     * path="/api/v1/inventories/audit",
     * summary="Audit inventori (SOAP)",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Berhasil diaudit"),
     * @OA\Response(response=500, description="Gagal audit")
     * )
     */
    public function sendAudit(Request $request)
    {
        $data = $request->all();

        try {
            $receiptNumber = $this->soapService->sendAudit(
                'TEAM-03', 
                'UpdateInventory', 
                json_encode($data)
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory berhasil diupdate dan sudah diaudit.',
                'receipt_number' => $receiptNumber
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal terhubung ke server audit: ' . $e->getMessage()
            ], 500);
        }
    }
}