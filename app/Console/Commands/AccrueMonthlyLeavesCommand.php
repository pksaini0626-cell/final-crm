<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Console\Command;

class AccrueMonthlyLeavesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:accrue {--month= : Specific YYYY-MM month, defaults to current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrue monthly leaves (1 EL, 0.5 CL) for all Permanent employees';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetMonth = $this->option('month') ?: now()->format('Y-m');
        $this->info("Accruing leaves for month: {$targetMonth}");

        $users = User::where('is_active', true)->get();
        $accruedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            if (strtolower($user->computed_employment_type) !== 'permanent') {
                $skippedCount++;
                continue;
            }

            $leaveBalance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['el_balance' => 0, 'cl_balance' => 0, 'ul_balance' => 0]
            );

            if ($leaveBalance->accrueForMonth($targetMonth)) {
                $accruedCount++;
                $this->line("Accrued leaves for: {$user->alias_name} ({$user->email})");
            } else {
                $skippedCount++;
            }
        }

        $this->info("Completed. Accrued for {$accruedCount} users. Skipped {$skippedCount} users.");
        return Command::SUCCESS;
    }
}
