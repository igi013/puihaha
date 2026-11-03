<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JjkMemberSeeder extends Seeder
{
    public function run(): void
    {
        // Fictional demonstration records; categories and levels are sample assignments.
        $characters = [
            ['Yuji', 'Itadori', 'yuji.itadori@example.test', 'Student', 'Basic'],
            ['Megumi', 'Fushiguro', 'megumi.fushiguro@example.test', 'Student', 'Silver'],
            ['Nobara', 'Kugisaki', 'nobara.kugisaki@example.test', 'Student', 'Silver'],
            ['Satoru', 'Gojo', 'satoru.gojo@example.test', 'Regular', 'Platinum'],
            ['Kento', 'Nanami', 'kento.nanami@example.test', 'Regular', 'Gold'],
            ['Maki', 'Zenin', 'maki.zenin@example.test', 'Student', 'Gold'],
        ];

        DB::transaction(function () use ($characters) {
            foreach ($characters as [$first, $last, $email, $category, $level]) {
                $newEmail = strtolower($first).'@gmail.jjk';
                $member = Member::whereIn('email', [$email, $newEmail])->first();
                if ($member) {
                    $member->update([
                        'email' => $newEmail,
                        'membership_number' => 'GRADE-'.str_pad((string) $member->id, 6, '0', STR_PAD_LEFT),
                    ]);
                    continue;
                }
                Member::register([
                    'email' => $newEmail,
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => null,
                    'address' => 'Tokyo Jujutsu High — fictional sample address',
                    'membership_category_id' => MembershipCategory::where('name', $category)->firstOrFail()->id,
                    'membership_level_id' => MembershipLevel::where('name', $level)->firstOrFail()->id,
                    'status' => 'active',
                ]);
            }
        });

        $this->command?->info('Six JJK sample members are available. Existing records were preserved.');
    }
}
