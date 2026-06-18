<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Inventory Service API Documentation",
    version: "1.0.0",
    description: "Dokumentasi Kontrak Tugas 2 - Ekosistem Fulfillment - Inventory Service (Dhika)"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Localhost Server Utama"
)]

// --- PERUBAHAN KRUSIAL: MENGUBAH POP-UP MENJADI APIKEYAUTH (X-IAE-KEY) ---
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-IAE-KEY",
    in: "header"
)]
class SwaggerDocs
{
    // ==========================================
    // ENDPOINT 1: List Semua Barang (GET)
    // ==========================================
    #[OA\Get(
        path: "/api/v1/inventories",
        summary: "Melihat daftar barang yang tersedia di gudang",
        tags: ["Inventory Management"],
        security: [["ApiKeyAuth" => []]], // Mengunci menggunakan ApiKeyAuth
        responses: [
            new OA\Response(response: 200, description: "Berhasil mengambil daftar barang"),
            new OA\Response(response: 401, description: "Unauthenticated / API Key Salah atau Tidak Ada")
        ]
    )]
    public function listInventories() {}

    // ==========================================
    // ENDPOINT 2: Detail Barang & Cek Stok (GET)
    // ==========================================
    #[OA\Get(
        path: "/api/v1/inventories/{id}",
        summary: "Mengambil detail barang dan mengecek ketersediaan stok",
        tags: ["Inventory Management"],
        security: [["ApiKeyAuth" => []]], // Mengunci menggunakan ApiKeyAuth
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID unik barang",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Detail data ditemukan"),
            new OA\Response(response: 401, description: "Unauthenticated / API Key Salah atau Tidak Ada")
        ]
    )]
    public function detailInventory() {}

    // ==========================================
    // ENDPOINT 3: Proses Pencatatan QC (POST)
    // ==========================================
    #[OA\Post(
        path: "/api/v1/inventories/qc",
        summary: "Memproses barang order sekaligus mencatat hasil QC",
        tags: ["Quality Control"],
        security: [["ApiKeyAuth" => []]], // Mengunci menggunakan ApiKeyAuth
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["order_id", "qc_status"],
                properties: [
                    new OA\Property(property: "order_id", type: "integer", example: 101),
                    new OA\Property(property: "qc_status", type: "string", example: "PASSED"),
                    new OA\Property(property: "notes", type: "string", example: "Barang aman pas sesuai pesanan")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Hasil QC berhasil dicatat"),
            new OA\Response(response: 401, description: "Unauthenticated / API Key Salah atau Tidak Ada")
        ]
    )]
    public function processQC() {}
}