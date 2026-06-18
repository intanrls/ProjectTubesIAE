# Expedition Service (Meilisya Nabila Siregar) - Tugas 2 IAE
Repositori ini berisi layanan **Expedition Service** berbasis Laravel yang dibuat untuk memenuhi Tugas 2 Integrasi Aplikasi Enterprise. Service ini digunakan dalam proses bisnis fulfillment, khususnya pada tahap pembuatan dan pengelolaan data pengiriman setelah order diproses dan barang dinyatakan lolos Quality Control oleh Inventory Service.

Layanan ini mendukung komunikasi melalui **REST API** yang didokumentasikan menggunakan **Swagger L5**, serta **GraphQL API** menggunakan Lighthouse dan GraphQL Playground. Seluruh endpoint diamankan menggunakan validasi API Key `X-IAE-KEY` melalui Request Header.

## 📋 Kesesuaian dengan Ketentuan Tugas

* **Fungsionalitas REST (40%)**: Menyediakan 3 endpoint REST Expedition Service dengan status code yang sesuai dan format JSON yang konsisten.
* **API Documentation (20%)**: Dokumentasi interaktif Swagger UI tersedia dan menampilkan seluruh endpoint Expedition Service.
* **GraphQL Implementation (20%)**: Query data pengiriman dapat dilakukan melalui GraphQL Playground dengan field yang dapat dipilih sesuai kebutuhan client.
* **Security & Standard (10%)**: Endpoint diamankan menggunakan validasi API Key pada Header `X-IAE-KEY`.

## 🏗️ Struktur Proyek Laravel

```text
expedition-service/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           └── ExpeditionController.php
│   │   └── Middleware/
│   │       └── CheckIaeKey.php
│   └── Models/
│       └── Expedition.php
├── bootstrap/
│   └── app.php
├── database/
│   └── migrations/
│       └── create_expeditions_table.php
├── graphql/
│   └── schema.graphql
├── routes/
│   └── api.php
├── README.md
└── prompt-log.md
```

## 🔑 Informasi Keamanan API Key

Seluruh request ke REST API wajib menyertakan API Key berikut pada Request Header:

```http
Header Key: X-IAE-KEY
Header Value: 102022400313
```

Jika API Key tidak dicantumkan atau tidak sesuai, sistem akan mengembalikan response unauthorized.

## 🌐 Endpoint REST API

| Method | Endpoint                   | Deskripsi                                            |
| ------ | -------------------------- | ---------------------------------------------------- |
| GET    | `/api/v1/expeditions`      | Melihat daftar semua data pengiriman                 |
| GET    | `/api/v1/expeditions/{id}` | Melihat detail pengiriman berdasarkan ID             |
| POST   | `/api/v1/expeditions`      | Membuat data pengiriman baru setelah barang lolos QC |

## 📖 Alur Pengujian REST API

### 1. Melihat Daftar Pengiriman

Request:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/expeditions" -Method Get -Headers @{"X-IAE-KEY"="102022400313"} | ConvertTo-Json -Depth 5
```

Response berhasil:

```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [],
  "meta": {
    "service_name": "Expedition-Service",
    "api_version": "v1"
  }
}
```

### 2. Melihat Detail Pengiriman

Request:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/expeditions/1" -Method Get -Headers @{"X-IAE-KEY"="102022400313"} | ConvertTo-Json -Depth 5
```

Response berhasil akan menampilkan detail data pengiriman berdasarkan ID yang dikirimkan.

### 3. Membuat Data Pengiriman Baru

Request:

```powershell
$body = @{
  order_id = 2
  customer_name = "Meilisya Nabila"
  customer_address = "Jl. Buah Batu No. 1, Bandung"
  courier_name = "JNE"
  tracking_number = "EXP-2026-002"
  shipping_status = "processing"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/expeditions" -Method Post -Headers @{"X-IAE-KEY"="102022400313"} -ContentType "application/json" -Body $body | ConvertTo-Json -Depth 5
```

Response berhasil:

```json
{
  "status": "success",
  "message": "Expedition created successfully",
  "data": {
    "id": 2,
    "order_id": 2,
    "customer_name": "Meilisya Nabila",
    "customer_address": "Jl. Buah Batu No. 1, Bandung",
    "courier_name": "JNE",
    "tracking_number": "EXP-2026-002",
    "shipping_status": "processing"
  },
  "meta": {
    "service_name": "Expedition-Service",
    "api_version": "v1"
  }
}
```

## 📌 Format Response Error

```json
{
  "status": "error",
  "message": "Unauthorized. Invalid or missing API Key",
  "errors": null
}
```

## 📄 Swagger Documentation

Swagger UI dapat diakses melalui:

```text
http://127.0.0.1:8000/api/documentation
```

Endpoint yang tersedia pada Swagger:

* `GET /api/v1/expeditions`
* `GET /api/v1/expeditions/{id}`
* `POST /api/v1/expeditions`

Swagger juga menyediakan fitur **Authorize** untuk memasukkan API Key pada header `X-IAE-KEY`.

## 📊 Pengujian GraphQL API

GraphQL Playground dapat diakses melalui:

```text
http://127.0.0.1:8000/graphql-playground
```

Contoh query GraphQL:

```graphql
{
  expeditions {
    id
    order_id
    customer_name
    courier_name
    tracking_number
    shipping_status
  }
}
```

Query tersebut digunakan untuk mengambil data pengiriman dengan field yang dapat dipilih sesuai kebutuhan client.

## ⚡ Cara Menjalankan Aplikasi

Install dependency:

```bash
composer install
```

Generate application key:

```bash
php artisan key:generate
```

Jalankan migration:

```bash
php artisan migrate
```

Generate Swagger:

```bash
php artisan l5-swagger:generate
```

Jalankan server Laravel:

```bash
php artisan serve
```

Akses aplikasi melalui:

```text
http://127.0.0.1:8000
```
