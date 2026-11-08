<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Buku\Models\Buku;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 5],
            ['Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 3],
            ['Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', 2009, 4],
            ['Pulang', 'Tere Liye', 'Republika', 2015, 6],
            ['Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia', 2002, 2],
            ['Ayat-Ayat Cinta', 'Habiburrahman El Shirazy', 'Republika', 2004, 7],
            ['Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 8],
            ['Atomic Habits', 'James Clear', 'Gramedia', 2019, 10],
            ['Sapiens', 'Yuval Noah Harari', 'KPG', 2017, 4],
            ['Laut Bercerita', 'Leila S. Chudori', 'KPG', 2017, 3],
            ['Dilan 1990', 'Pidi Baiq', 'Pastel Books', 2014, 5],
            ['Rich Dad Poor Dad', 'Robert Kiyosaki', 'Gramedia', 2011, 6],
        ];

        foreach ($daftar as [$judul, $penulis, $penerbit, $tahun, $stok]) {
            Buku::create([
                'judul' => $judul,
                'penulis' => $penulis,
                'penerbit' => $penerbit,
                'tahun_terbit' => $tahun,
                'stok' => $stok,
            ]);
        }
    }
}