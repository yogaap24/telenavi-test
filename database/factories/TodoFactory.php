<?php

namespace Database\Factories;

use App\Models\Entity\Todo;
use App\Models\Entity\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoFactory extends Factory
{
    protected $model = Todo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'completed', 'in_progress'];
        $priorities = ['low', 'medium', 'high'];

        static $lastStatus = null;
        static $lastPriority = null;

        // Generate status yang tidak sama dengan sebelumnya
        do {
            $status = $statuses[array_rand($statuses)];
        } while ($status === $lastStatus);

        // Generate priority yang tidak sama dengan sebelumnya
        do {
            $priority = $priorities[array_rand($priorities)];
        } while ($priority === $lastPriority);

        $lastStatus = $status;
        $lastPriority = $priority;

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'title' => fake()->sentence(6),
            'assignee' => fake()->name(),
            'due_date' => now()->addDay(),
            'time_tracked' => fake()->randomFloat(2, 0, 10),
            'status' => $status,
            'priority' => $priority,
        ];
    }
}
