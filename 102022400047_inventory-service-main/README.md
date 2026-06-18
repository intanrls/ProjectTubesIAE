# Inventory & Quality Control Service (GraphQL API)

Proyek ini adalah sebuah layanan API berbasis **GraphQL** yang dibangun menggunakan **Laravel** dan package **Lighthouse GraphQL**. Layanan ini dirancang untuk mengelola data inventaris barang serta mencatat aktivitas pengecekan kualitas fisik barang (*Quality Control*).

## Spesifikasi Teknologi
* **Framework:** Laravel (v10/v11)
* **API Protocol:** GraphQL
* **GraphQL Server:** Nuwave Lighthouse
* **Database:** MySQL / MariaDB
* **API Testing Tool:** Postman / GraphQL Playground

---

## Fitur & Struktur Schema GraphQL

Layanan ini mendukung operasi query (membaca data) dan mutation (memanipulasi data) dengan struktur schema sebagai berikut:

### 1. Object Types
* **`Inventory`**: Merepresentasikan data barang di gudang (`id`, `nama_barang`, `stok`, `status_qc`, `kondisi_barang`).
* **`QCResponse`**: Merepresentasikan struktur respons setelah melakukan input data Quality Control.

### 2. Query
* `listInventories`: Mengambil seluruh daftar inventaris barang yang tersedia di database.
* `detailInventory(id: ID!)`: Mengambil informasi detail dari satu barang spesifik berdasarkan ID-nya.

### 3. Mutation
* `storeQC(order_id: Int!, qc_status: String!, notes: String)`: Menyimpan data pengecekan kualitas barang baru.

---

## Langkah Instalasi & Penggunaan

### 1. Clone & Setup Environment
```bash
git clone <repository-url>
cd inventory-service
composer install
cp .env.example .env
php artisan key:generate