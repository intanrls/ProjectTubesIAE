<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        \Illuminate\Support\Facades\DB::table('inventories')->delete();

        \Illuminate\Support\Facades\DB::table('inventories')->insert([
            [
                'id' => 1,
                'nama_barang' => 'Buku Pemrograman',
                'stok' => 100,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'nama_barang' => 'Buku Latsol UTBK',
                'stok' => 50,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'nama_barang' => 'Buku Cerita Nabi',
                'stok' => 10,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'nama_barang' => 'Kamus Bahasa Inggris',
                'stok' => 10,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 5,
                'nama_barang' => 'Buku Tulis Premium Pack',
                'stok' => 50,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 6,
                'nama_barang' => 'Buku Panduan Lolos Tes CPNS',
                'stok' => 5,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 7,
                'nama_barang' => 'Novel Matahari - Tere Liye',
                'stok' => 15,
                'status_qc' => 'PASS',
                'kondisi_barang' => 'Baik',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
