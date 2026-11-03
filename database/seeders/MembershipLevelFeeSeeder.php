<?php

namespace Database\Seeders;

use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipLevelFeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['Basic' => 5000, 'Silver' => 10000, 'Gold' => 15000, 'Platinum' => 25000] as $name => $fee) {
                MembershipLevel::where('name', $name)->firstOrFail()->update(['fee' => $fee]);
            }
        });

        $this->command?->info('Basic, Silver, Gold and Platinum fees updated in PHP.');
    }
}
