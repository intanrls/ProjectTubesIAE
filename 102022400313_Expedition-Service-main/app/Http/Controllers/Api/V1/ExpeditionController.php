<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expedition;
use Illuminate\Http\Request;
use App\Services\IaeCloudService;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Expedition Service API",
    description: "REST & GraphQL API untuk Expedition Service (Meilisya Nabila Siregar) berbasis Laravel. Seluruh endpoint diamankan menggunakan API Key melalui header X-IAE-KEY."
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local Development Server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-IAE-KEY",
    in: "header",
    description: "API Key menggunakan NIM Anda"
)]

class ExpeditionController extends Controller
{

#[OA\Get(
    path: "/api/v1/expeditions",
    summary: "Melihat daftar semua pengiriman",
    tags: ["Expedition Service"],
    security: [["ApiKeyAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Data retrieved successfully"),
        new OA\Response(response: 401, description: "Unauthorized")
    ]
)]

    public function index()
    {
        $expeditions = Expedition::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $expeditions,
            'meta' => [
                'service_name' => 'Expedition-Service',
                'api_version' => 'v1'
            ]
        ], 200);
    }

    #[OA\Get(
    path: "/api/v1/expeditions/{id}",
    summary: "Melihat detail pengiriman berdasarkan ID",
    tags: ["Expedition Service"],
    security: [["ApiKeyAuth" => []]],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        )
    ],

responses: [
        new OA\Response(response: 200, description: "Data retrieved successfully"),
        new OA\Response(response: 404, description: "Resource not found"),
        new OA\Response(response: 401, description: "Unauthorized")
    ]
)]

    public function show($id)
    {
        $expedition = Expedition::find($id);

        if (!$expedition) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'errors' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $expedition,
            'meta' => [
                'service_name' => 'Expedition-Service',
                'api_version' => 'v1'
            ]
        ], 200);
    }

#[OA\Post(
    path: "/api/v1/expeditions",
    summary: "Membuat data pengiriman baru",
    tags: ["Expedition Service"],
    security: [["ApiKeyAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["order_id", "customer_name", "customer_address", "courier_name"],
            properties: [
                new OA\Property(property: "order_id", type: "string", example: "ORD-001", description: "ID Order dari Order Service (format: ORD-XXX)"),
                new OA\Property(property: "customer_name", type: "string", example: "Meilisya Nabila"),
                new OA\Property(property: "customer_address", type: "string", example: "Jl. Buah Batu No. 1, Bandung"),
                new OA\Property(property: "courier_name", type: "string", example: "JNE"),
                new OA\Property(property: "tracking_number", type: "string", example: "EXP-2026-001"),
                new OA\Property(property: "shipping_status", type: "string", example: "processing")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Expedition created successfully"),
        new OA\Response(response: 401, description: "Unauthorized")
    ]
)]

public function store(Request $request)
{
    $validated = $request->validate([
        'order_id'       => 'required',
        'customer_name'  => 'required|string|max:255',
        'customer_address'=> 'required|string',
        'courier_name'   => 'required|string|max:255',
        'tracking_number'=> 'nullable|string|max:255|unique:expeditions,tracking_number',
        'shipping_status'=> 'nullable|string|max:255',
    ]);

    // Normalisasi order_id: jika integer (misal 1), ubah ke format ORD-001
    $rawOrderId = $validated['order_id'];
    if (is_numeric($rawOrderId)) {
        $validated['order_id'] = 'ORD-' . str_pad((int) $rawOrderId, 3, '0', STR_PAD_LEFT);
    } else {
        $validated['order_id'] = strtoupper(trim($rawOrderId));
    }

    if (empty($validated['tracking_number'])) {
        $validated['tracking_number'] = 'TRK-IAE-' . strtoupper(bin2hex(random_bytes(4)));
    }

    $expedition = Expedition::create($validated);

    $iaeCloud = new IaeCloudService();

    $receiptNumber = $iaeCloud->sendSoapAudit($expedition->toArray());
    $rabbitPublished = $iaeCloud->publishRabbitMq($expedition->toArray());

    if ($receiptNumber) {
        $expedition->update(['receipt_number' => $receiptNumber]);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Expedition created successfully',
        'data' => $expedition,
        'integration' => [
            'soap_audit' => [
                'status' => $receiptNumber ? 'success' : 'failed',
                'receipt_number' => $receiptNumber,
            ],
            'rabbitmq' => [
                'status' => $rabbitPublished ? 'success' : 'failed',
                'event' => 'expedition.created',
            ],
        ],
        'meta' => [
            'service_name' => 'Expedition-Service',
            'api_version' => 'v1'
        ]
    ], 201);
  }
} 
