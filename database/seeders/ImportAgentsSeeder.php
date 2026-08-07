<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ImportAgentsSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('user-table.txt');
        if (!file_exists($filePath)) {
            $this->command->error("user-table.txt file not found!");
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $importedCount = 0;

        if (class_exists(Role::class)) {
            Role::findOrCreate('agent');
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '(')) {
                continue;
            }

            $str = rtrim($line, ',;');
            $str = ltrim($str, '(');
            $str = rtrim($str, ')');

            // Parse values using str_getcsv with single quote delimiter
            $values = str_getcsv($str, ',', "'");

            if (count($values) < 11) {
                continue;
            }

            $name = trim($values[2]);
            $aliasName = ($values[3] !== 'NULL' && $values[3] !== 'null' && $values[3] !== '') ? trim($values[3]) : null;
            $email = trim($values[4]);
            $ext = ($values[5] !== 'NULL' && $values[5] !== 'null' && $values[5] !== '') ? trim($values[5]) : null;
            $phone = ($values[6] !== 'NULL' && $values[6] !== 'null' && $values[6] !== '') ? trim($values[6]) : null;
            $password = trim($values[8]);
            $role = trim($values[9]);
            $isActive = (int)trim($values[10]);

            if ($role === 'agent' && $isActive === 1) {
                DB::table('users')->updateOrInsert(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'alias_name' => $aliasName,
                        'extension' => $ext,
                        'contact' => $phone,
                        'password' => $password,
                        'role' => 'agent',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $user = User::where('email', $email)->first();
                if ($user && method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole('agent');
                    } catch (\Throwable $e) {
                        // ignore if role table not set up
                    }
                }

                $importedCount++;
            }
        }

        $this->command->info("Successfully imported {$importedCount} active agent profiles.");
    }
}
