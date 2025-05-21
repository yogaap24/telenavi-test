<?php

namespace Database\Seeders;

use App\Models\Entity\Todo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek jumlah todo yang sudah ada
        $existingCount = Todo::count();
        $targetCount = 100000;

        // Jika sudah ada 100000 todo, lewati proses
        if ($existingCount >= $targetCount) {
            $this->command->info("Sudah ada {$existingCount} todo, melewati proses seed");
            return;
        }

        // Buat daftar beberapa assignee tetap
        $fixedAssignees = [
            'Budi Santoso',
            'Dewi Putri',
            'Ahmad Ridwan',
            'Siti Rahayu',
            'Joko Wibowo'
        ];

        // Hitung berapa banyak todo yang perlu dibuat
        $toCreate = $targetCount - $existingCount;
        $this->command->info("Membuat {$toCreate} todo baru");

        $statuses = ['pending', 'completed', 'in_progress'];
        $priorities = ['low', 'medium', 'high'];

        $baseDate = Carbon::now()->addDay();
        $dueDate = $baseDate->copy();
        $increment = 1;
        $cycle = 0;

        // Dapatkan status dan priority terakhir jika sudah ada data
        if ($existingCount > 0) {
            $lastTodo = Todo::orderBy('id', 'desc')->first();
            $lastStatus = $lastTodo->status;
            $currentStatus = $lastTodo->status;
            $lastPriority = $lastTodo->priority;
            $currentPriority = $lastTodo->priority;

            // Set due date berdasarkan todo terakhir
            $dueDate = Carbon::parse($lastTodo->due_date);

            // Menentukan increment dan cycle berdasarkan tanggal terakhir
            $daysSinceBase = $dueDate->diffInDays($baseDate);

            if ($daysSinceBase >= 90) {
                $increment = 2;
                $cycle = 1;
            } else if ($daysSinceBase >= 60 && $cycle == 1) {
                $increment = 3;
                $cycle = 0;
            } else {
                $increment = 1;
                $cycle = 0;
            }
        } else {
            $lastStatus = null;
            $currentStatus = null;
            $lastPriority = null;
            $currentPriority = null;
        }

        $nextStatus = null;
        $nextPriority = null;

        // Membuat beberapa tanggal untuk grup data (untuk memastikan ada 5-10 tanggal yang sama)
        $specialDates = [];

        // Buat 3 grup tanggal khusus (masing-masing akan digunakan untuk beberapa todo)
        $date1 = Carbon::now()->addDays(rand(5, 15));
        $date2 = Carbon::now()->addDays(rand(20, 30));
        $date3 = Carbon::now()->addDays(rand(40, 50));

        // Tambahkan ke array tanggal khusus
        $specialDates[] = $date1->format('Y-m-d');
        $specialDates[] = $date2->format('Y-m-d');
        $specialDates[] = $date3->format('Y-m-d');

        // Siapkan array untuk melacak berapa kali setiap tanggal khusus digunakan
        $specialDateUsage = array_fill_keys($specialDates, 0);

        // Jumlah maksimal penggunaan untuk setiap tanggal khusus
        $maxUsagePerDate = rand(5, 10);

        // Buat tracking untuk assignee tetap
        $fixedAssigneeTodos = array_fill_keys($fixedAssignees, 0);

        // Buat todo sesuai yang diminta
        $this->command->getOutput()->progressStart($toCreate);

        for ($i = 0; $i < $toCreate; $i++) {
            // Menentukan apakah kita akan menggunakan tanggal khusus untuk todo ini
            $useSpecialDate = false;
            $specialDate = null;

            // Cek setiap 25 todo, apakah kita perlu menambahkan tanggal yang sama
            if ($i % 25 === 0) {
                // Ambil tanggal khusus secara acak
                $specialDate = $specialDates[array_rand($specialDates)];

                // Jika tanggal ini belum mencapai batas penggunaan, gunakan
                if ($specialDateUsage[$specialDate] < $maxUsagePerDate) {
                    $useSpecialDate = true;
                    $specialDateUsage[$specialDate]++;
                }
            }

            // Logic for due_date (hanya digunakan jika tidak menggunakan tanggal khusus)
            if (!$useSpecialDate) {
                if ($i > 0 || $existingCount > 0) {
                    if ($cycle == 0 && $dueDate->diffInDays($baseDate) >= 90) {
                        // Reset after 90 days, increment +2
                        $dueDate = $baseDate->copy()->addDays(floor($dueDate->diffInDays($baseDate) / 2));
                        $increment = 2;
                        $cycle = 1;
                    } else if ($cycle == 1 && $dueDate->diffInDays($baseDate) >= 60) {
                        // Reset after 60 more days, increment +3
                        $dueDate = $baseDate->copy()->addDays(floor($dueDate->diffInDays($baseDate) / 2));
                        $increment = 3;
                        $cycle = 0;
                    } else {
                        // Regular increment
                        $dueDate->addDays($increment);
                    }
                }
            }

            // Status logic - avoid same values in consecutive records
            do {
                $nextStatus = $statuses[array_rand($statuses)];
            } while ($nextStatus === $currentStatus || $nextStatus === $lastStatus);

            $lastStatus = $currentStatus;
            $currentStatus = $nextStatus;

            // Priority logic - avoid same values in consecutive records
            do {
                $nextPriority = $priorities[array_rand($priorities)];
            } while ($nextPriority === $currentPriority || $nextPriority === $lastPriority);

            $lastPriority = $currentPriority;
            $currentPriority = $nextPriority;

            // Tentukan tanggal yang akan digunakan
            $todoDate = $useSpecialDate ? $specialDate : $dueDate->copy()->format('Y-m-d');

            // Pilih assignee - menggunakan fixed assignee jika belum punya 5 todo
            $assigneeToUse = null;

            // Peluang 20% untuk menggunakan fixed assignee
            if (rand(1, 5) === 1) {
                // Pilih dari fixed assignee yang belum punya 5 todo
                $availableFixedAssignees = array_filter($fixedAssigneeTodos, function($count) {
                    return $count < 5;
                });

                if (!empty($availableFixedAssignees)) {
                    $assigneeToUse = array_rand($availableFixedAssignees);
                    $fixedAssigneeTodos[$assigneeToUse]++;
                }
            }

            if ($assigneeToUse === null) {
                $assigneeToUse = fake()->name();
            }

            Todo::create([
                'title' => fake()->sentence(6),
                'assignee' => $assigneeToUse,
                'due_date' => $todoDate,
                'time_tracked' => fake()->randomFloat(2, 0, 10),
                'status' => $currentStatus,
                'priority' => $currentPriority,
            ]);

            $this->command->getOutput()->progressAdvance();
        }

        // Cek jika ada fixed assignee yang belum memiliki 5 todo
        foreach ($fixedAssignees as $assignee) {
            $count = $fixedAssigneeTodos[$assignee];
            if ($count < 5) {
                $remaining = 5 - $count;
                $this->command->info("Membuat {$remaining} todo tambahan untuk {$assignee}");

                // Buat todo tambahan dengan variasi status dan priority
                for ($j = 0; $j < $remaining; $j++) {
                    Todo::create([
                        'title' => "Todo khusus " . ($j + 1) . " untuk " . $assignee,
                        'assignee' => $assignee,
                        'due_date' => Carbon::now()->addDays(rand(1, 30))->format('Y-m-d'),
                        'time_tracked' => fake()->randomFloat(2, 0, 10),
                        'status' => $statuses[$j % count($statuses)],
                        'priority' => $priorities[$j % count($priorities)],
                    ]);
                }
            }
        }

        // Tampilkan statistik penggunaan tanggal khusus
        $this->command->info("Penggunaan tanggal khusus:");
        foreach ($specialDateUsage as $date => $count) {
            $this->command->info("  - {$date}: {$count} todo");
        }

        // Tampilkan statistik fixed assignees
        $this->command->info("Jumlah todo untuk fixed assignees:");
        foreach ($fixedAssigneeTodos as $assignee => $count) {
            $todoCount = Todo::where('assignee', $assignee)->count();
            $this->command->info("  - {$assignee}: {$todoCount} todo");
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("Berhasil membuat {$toCreate} todo");
    }
}
