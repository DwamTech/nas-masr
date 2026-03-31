<?php

namespace Database\Factories;

use App\Models\BackupHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupHistoryFactory extends Factory
{
    protected $model = BackupHistory::class;

    public function definition(): array
    {
        $types = ['db', 'upload'];
        $statuses = ['success', 'failed', 'pending'];

        return [
            'file_name'  => 'backup_' . $this->faker->dateTime->format('Y_m_d_His') . '_db.sql.gz',
            'file_path'  => 'backups/backup_' . $this->faker->dateTime->format('Y_m_d_His') . '_db.sql.gz',
            'type'       => $this->faker->randomElement($types),
            'status'     => $this->faker->randomElement($statuses),
            'size'       => $this->faker->numberBetween(1024, 10485760), // 1 KB to 10 MB
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the backup is successful.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    /**
     * Indicate that the backup failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the backup is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the backup is a database backup.
     */
    public function database(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'db',
        ]);
    }

    /**
     * Indicate that the backup is an uploaded backup.
     */
    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'upload',
        ]);
    }
}
