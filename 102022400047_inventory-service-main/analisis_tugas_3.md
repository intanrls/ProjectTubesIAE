cat > analisis_tugas_3.md << 'EOF'
# Analisis Tugas 3 — Inventory Service
**Nama:** Rahmandhika Muhammad El Kariem
**NIM:** 102022400047
**Tim:** TEAM-03
**Mata Kuliah:** BBK2HAB3 - Integrasi Aplikasi Enterprise

---

## 1. Penjelasan Transaksi Kritis

### A. Transaksi Penting — SOAP Audit

Pada sistem inventory service yang saya kerjakan, transaksi yang saya
pilih sebagai transaksi kritis adalah **Update Stok Inventory**
(`POST /api/inventory`).

Alasan saya memilih transaksi ini sebagai transaksi kritis adalah:

1. **Mengubah State Data Secara Permanen** — Ketika stok barang diubah,
   data di database langsung berubah dan berdampak ke seluruh sistem.
   Kalau ada kesalahan, efeknya langsung terasa ke operasional gudang.

2. **Butuh Bukti Transaksi yang Sah** — Berbeda dengan transaksi baca
   (GET), transaksi ini wajib tercatat di sistem audit pusat milik dosen
   (IAE SSO) dan mendapatkan ReceiptNumber sebagai bukti bahwa
   transaksi sudah diakui secara resmi.

3. **Tidak Bisa Dibatalkan Sembarangan** — Perubahan stok yang salah
   bisa menyebabkan kelebihan atau kekurangan stok yang merugikan,
   sehingga perlu validasi dan pencatatan yang ketat.

4. **Berdampak ke Departemen Lain** — Perubahan stok perlu diketahui
   oleh tim Sales dan Procurement, sehingga setelah SOAP sukses, event
   juga disebarkan via RabbitMQ.

**Skema Role Lokal yang Saya Terapkan:**

| Role | Hak Akses |
|------|-----------|
| admin | Bisa update stok, lihat semua inventory |
| staff_gudang | Bisa update stok |
| viewer | Hanya bisa lihat daftar inventory (GET) |

---

### B. Transaksi Broadcast — RabbitMQ

Selain SOAP, saya juga mengimplementasikan broadcast event ke RabbitMQ
setiap kali ada perubahan stok. Alasan saya menggunakan RabbitMQ:

- **Supaya Tidak Memblokir Response** — Broadcast dilakukan secara
  asinkron (menggunakan Laravel Job), jadi client tidak perlu menunggu
  proses broadcast selesai.
- **Loose Coupling** — Departemen lain bisa subscribe ke event
  inventory.stock.updated tanpa harus tahu detail implementasi saya.

---

## 2. Sequence Diagram

### Diagram 1 — Alur SSO Login (Modul 1)

sequenceDiagram
    actor Saya as Saya (Client)
    participant App as Inventory Service (Laravel)
    participant SSO as IAE SSO Server (Dosen)

    Saya->>App: POST /api/v1/sso/callback kirim email and password warga
    App->>SSO: POST /api/v1/auth/token teruskan ke server dosen

    alt Login berhasil
        SSO-->>App: 200 OK kasih access_token JWT
        App->>App: petakan user ke tabel roles lokal
        App-->>Saya: 200 OK kembalikan token ke client
    else Login gagal
        SSO-->>App: 401 Unauthorized
        App-->>Saya: 401 Gagal login ke IAE
    end

### Diagram 2 — Alur Transaksi Kritis SOAP (Modul 2)

sequenceDiagram
    actor Saya as Saya (Client)
    participant App as Inventory Service (Laravel)
    participant SSO as IAE SSO Server (Dosen)
    participant SOAP as IAE SOAP Audit Server (Dosen)
    participant DB as Database Lokal Saya

    Saya->>App: POST /api/inventory kirim inventory_id and quantity

    Note over App,SSO: Step 1 Minta M2M Token dulu
    App->>SSO: POST /api/v1/auth/token pakai api_key milik saya KEY-MHS-133
    SSO-->>App: kasih token M2M bukan token user biasa

    Note over App,SOAP: Step 2 Kirim SOAP XML ke server dosen
    App->>App: saya build SOAP Envelope XML isi TeamID ActivityName LogContent
    App->>SOAP: POST /soap/v1/audit pakai Bearer token M2M plus kirim XML

    alt SOAP berhasil
        SOAP-->>App: SUCCESS plus ReceiptNumber IAE-LOG-2026-15260B97
        App->>DB: simpan audit log ke database lokal saya
        App-->>Saya: 200 OK tampilkan receipt_number ke client
    else SOAP gagal
        SOAP-->>App: Error 4xx atau 5xx
        App-->>Saya: 500 Gagal konek ke server audit
    end

### Diagram 3 — Alur Broadcast RabbitMQ (Modul 3)

sequenceDiagram
    actor Saya as Saya (Client)
    participant App as Inventory Service (Laravel)
    participant Job as Background Job Laravel Queue
    participant SSO as IAE SSO Server (Dosen)
    participant MQ as IAE RabbitMQ Exchange (Dosen)
    participant Board as /board Papan Pengumuman Dosen

    Saya->>App: POST /api/v1/inventory/store kirim inventory_id and quantity
    App->>Job: dispatch job ke background tidak nunggu langsung balas
    App-->>Saya: 202 Accepted data sedang diproses

    Note over Job,Board: Proses berlangsung di background tanpa client tahu
    Job->>SSO: minta M2M token lagi pakai api_key saya
    SSO-->>Job: dapat access_token
    Job->>MQ: POST /api/v1/messages/publish kirim routing_key plus data event JSON

    alt Publish berhasil
        MQ-->>Job: 200 OK event masuk ke exchange
        Job->>Board: pesan saya muncul di papan pengumuman /board
    else Publish gagal
        MQ-->>Job: Error
        Job->>Job: catat di log dan coba ulang retry
    end

---

## 3. Capaian Teknis yang Sudah Saya Implementasikan

### Modul 1 — Federated SSO
- File: app/Http/Controllers/SsoController.php
- Cara kerja: Saya menangkap email and password dari client, lalu
  meneruskannya ke server SSO dosen. JWT yang saya terima dikembalikan
  ke client dan bisa dipakai untuk akses endpoint yang dilindungi.

### Modul 2 — SOAP XML Client
- File: app/Services/SoapAuditService.php
- Cara kerja: Saya melakukan transformasi data JSON ke SOAP Envelope
  XML, mengirimnya ke server dosen, lalu mem-parsing ReceiptNumber
  dari response XML.
- Bukti: ReceiptNumber yang saya terima: IAE-LOG-2026-15260B97

### Modul 3 — AMQP Publisher
- File: app/Jobs/ProcessInventoryMessage.php
- File: app/Http/Controllers/Api/V1/MessageController.php
- Cara kerja: Saya mengirim event JSON ke RabbitMQ dosen menggunakan
  Bearer M2M token. Pesan sudah terverifikasi muncul di /board.

### Modul 4 — Akuntabilitas Progres
- Rekap log prompting dengan AI tersedia di file promptlog.md
EOF