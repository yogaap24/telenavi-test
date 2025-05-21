<?php

namespace Database\Seeders;

use App\Models\Entity\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek jumlah user yang sudah ada
        $existingCount = User::count();

        // Jika belum ada 10 user, buat user baru
        $toCreate = 10 - $existingCount;

        if ($toCreate > 0) {
            $this->command->info("Membuat {$toCreate} user baru");
            User::factory($toCreate)->create();
        } else {
            $this->command->info("Sudah ada cukup data user, melewati proses seed");
        }
    }
}
