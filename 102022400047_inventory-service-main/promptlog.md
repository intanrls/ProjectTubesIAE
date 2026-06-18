# Prompt Log - Pengembangan Inventory GraphQL Service

Dokumen ini mencatat log interaksi, kendala, dan proses pemecahan masalah (*debugging*) selama proses pengerjaan dan konfigurasi API GraphQL berbasis Laravel Lighthouse.

## Log Aktivitas & Pemecahan Masalah

### 1. Pengisian Data Awal di phpMyAdmin
* **Konteks:** Melakukan input data testing langsung (*seed*) melalui antarmuka phpMyAdmin agar GraphQL memiliki data objek untuk ditarik.
* **Solusi/Hasil:** Mengisi tabel inventaris melalui menu *Insert* dengan mengosongkan kolom `id` (karena *auto-increment*), menginput data nama barang, jumlah stok, status QC, dan kondisi barang, lalu menyimpannya menggunakan tombol *Go/Kirim*.

### 2. Migrasi Testing dari GraphQL Playground ke Postman
* **Konteks:** Memindahkan workflow pengujian API dari ekosistem GraphQL Playground internal milik Laravel menuju aplikasi Postman agar standardisasi testing API terpenuhi.
* **Solusi/Hasil:** Mengubah HTTP Method menjadi `POST`, mengarahkan URL endpoint ke core API (`http://127.0.0.1:8000/graphql`), serta mengonfigurasi tipe raw body ke format *GraphQL*.

### 3. Penanganan Error: *Cannot return null for non-nullable field*
* **Konteks:** Saat melakukan pengujian query `listInventories` pertama kali di Postman, sistem mengembalikan error `Internal server error` berkode *debug* berikut:
  > `"Cannot return null for non-nullable field \"Inventory.item_name\"."`
* **Analisis Masalah:** Terjadi ketidaksesuaian (*mismatch*) antara penamaan field di file `schema.graphql` (yang menggunakan bahasa Inggris seperti `item_name`) dengan nama kolom fisik yang tersedia di database MySQL actual (yang menggunakan bahasa Indonesia seperti `nama_barang`). Karena bernilai `null` padahal field tersebut bersifat *required* (`String!`), GraphQL Engine memicu *crash exception*.
* **Solusi Pemecahan:**
  Melakukan refactoring pada file `schema.graphql` agar skema representasi tipe data `Inventory` merujuk langsung secara identik ke penamaan kolom database lokal (Solusi Penyelarasan Lapisan Schema):
  ```graphql
  type Inventory {
      id: ID!
      nama_barang: String!
      stok: Int!
      status_qc: String!
      kondisi_barang: String!
  }