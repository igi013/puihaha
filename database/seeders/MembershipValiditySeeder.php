<?php

namespace Database\Seeders;

use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipValiditySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['Basic' => 12, 'Silver' => 24, 'Gold' => 36, 'Platinum' => 60] as $name => $months) {
                MembershipLevel::where('name', $name)->firstOrFail()->update(['validity_months' => $months]);
            }
        });
    }
}
