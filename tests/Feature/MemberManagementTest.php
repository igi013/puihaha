<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    private function details(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Alex', 'last_name' => 'Rivera',
            'email' => 'alex@example.test', 'phone' => '09123456789',
            'address' => 'Test address',
            'membership_category_id' => MembershipCategory::first()->id,
            'membership_level_id' => MembershipLevel::first()->id,
            'status' => 'active',
        ], $overrides);
    }

    public function test_guests_and_non_admins_cannot_manage_members(): void
    {
        $this->get('/members')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/members')->assertForbidden();
        $this->post('/members', $this->details())->assertForbidden();
    }

    public function test_admin_can_register_view_and_edit_a_member(): void
    {
        $this->actingAs($this->admin());
        $this->get('/members/create')->assertOk();
        $this->post('/members', $this->details())->assertSessionHasNoErrors()->assertRedirect();
        $member = Member::firstOrFail();
        $this->assertMatchesRegularExpression('/^GRADE-\d{6}$/', $member->membership_number);
        $number = $member->membership_number;
        $this->get('/members/'.$member->id)->assertOk()->assertSee('Alex Rivera');
        $this->get('/members/'.$member->id.'/edit')->assertOk();
        $this->put('/members/'.$member->id, $this->details(['first_name' => 'Jamie']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('members', ['id' => $member->id, 'first_name' => 'Jamie', 'membership_number' => $number]);
    }

    public function test_validation_rejects_invalid_and_duplicate_details(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details(['first_name' => '', 'email' => 'invalid', 'membership_category_id' => 9999, 'status' => 'bad']))
            ->assertSessionHasErrors(['first_name', 'email', 'membership_category_id', 'status']);
        $this->post('/members', $this->details())->assertSessionHasNoErrors();
        $this->post('/members', $this->details())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('members', 1);
    }

    public function test_numbers_are_unique_and_cannot_be_overwritten_by_form_input(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details(['membership_number' => 'DUPLICATE']));
        $this->post('/members', $this->details(['email' => 'second@example.test', 'membership_number' => 'DUPLICATE']));
        $this->assertSame(2, Member::distinct()->count('membership_number'));
        $this->assertDatabaseMissing('members', ['membership_number' => 'DUPLICATE']);
    }

    public function test_name_category_level_and_status_search(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details());
        $this->post('/members', $this->details(['first_name' => 'Morgan', 'email' => 'morgan@example.test', 'membership_category_id' => MembershipCategory::skip(1)->first()->id]));
        $this->get('/members?q=Alex%20Rivera')->assertOk()->assertSee('alex@example.test')->assertDontSee('morgan@example.test');
        $this->get('/members?category='.MembershipCategory::first()->id)->assertOk()->assertSee('alex@example.test')->assertDontSee('morgan@example.test');
        $this->get('/members?level='.MembershipLevel::first()->id)->assertOk()->assertSee('alex@example.test');
        $this->get('/members?status=inactive')->assertOk()->assertDontSee('alex@example.test');
    }

    public function test_deactivation_retains_profile_and_edit_can_reactivate(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details());
        $member = Member::firstOrFail();
        $this->patch('/members/'.$member->id.'/deactivate')->assertRedirect('/members');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'inactive']);
        $this->get('/members/'.$member->id)->assertOk()->assertSee('Inactive');
        $this->put('/members/'.$member->id, $this->details())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'active']);
    }

    public function test_admin_can_permanently_delete_a_member_only_after_confirmation(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details());
        $member = Member::firstOrFail();
        $this->delete('/members/'.$member->id)->assertSessionHasErrors('confirm_delete');
        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->delete('/members/'.$member->id, ['confirm_delete' => '1'])->assertRedirect('/members');
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->get('/members/'.$member->id)->assertNotFound();
    }

    public function test_non_admin_cannot_delete_or_deactivate_members(): void
    {
        $this->actingAs($this->admin());
        $this->post('/members', $this->details());
        $member = Member::firstOrFail();
        $this->actingAs(User::factory()->create());
        $this->delete('/members/'.$member->id, ['confirm_delete' => '1'])->assertForbidden();
        $this->patch('/members/'.$member->id.'/deactivate')->assertForbidden();
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'active']);
    }

    public function test_admin_login_and_logout(): void
    {
        $admin = $this->admin();
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/members');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
