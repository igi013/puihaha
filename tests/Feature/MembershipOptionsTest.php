<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function signInAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin);
    }

    public function test_options_require_an_administrator(): void
    {
        foreach (['categories', 'levels'] as $path) {
            $this->get('/'.$path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach (['categories', 'levels'] as $path) {
            $this->get('/'.$path)->assertForbidden();
            $this->post('/'.$path, ['name' => 'Unauthorized'])->assertForbidden();
        }
    }

    public function test_category_create_read_update_and_delete(): void
    {
        $this->signInAdmin();
        $this->get('/categories')->assertOk()->assertSee('Senior Citizen');
        $this->get('/categories/create')->assertOk();
        $this->post('/categories', ['name' => 'Alumni'])->assertSessionHasNoErrors()->assertRedirect();
        $category = MembershipCategory::where('name', 'Alumni')->firstOrFail();
        $this->get('/categories/'.$category->id)->assertOk()->assertSee('Alumni');
        $this->get('/categories/'.$category->id.'/edit')->assertOk();
        $this->put('/categories/'.$category->id, ['name' => 'Graduate'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_categories', ['id' => $category->id, 'name' => 'Graduate']);
        $this->delete('/categories/'.$category->id)->assertRedirect('/categories');
        $this->assertDatabaseMissing('membership_categories', ['id' => $category->id]);
    }

    public function test_level_create_read_update_and_delete(): void
    {
        $this->signInAdmin();
        $this->get('/levels')->assertOk()->assertSee('Not set');
        $this->get('/levels/create')->assertOk();
        $details = ['name' => 'Diamond', 'fee' => '1250.50', 'validity_months' => 12, 'benefits' => 'Priority support'];
        $this->post('/levels', $details)->assertSessionHasNoErrors()->assertRedirect();
        $level = MembershipLevel::where('name', 'Diamond')->firstOrFail();
        $this->get('/levels/'.$level->id)->assertOk()->assertSee('1,250.50')->assertSee('12 months')->assertSee('Priority support');
        $this->get('/levels/'.$level->id.'/edit')->assertOk();
        $this->put('/levels/'.$level->id, array_replace($details, ['fee' => '0', 'validity_months' => 6]))->assertSessionHasNoErrors();
        $this->assertSame('0.00', $level->fresh()->fee);
        $this->delete('/levels/'.$level->id)->assertRedirect('/levels');
        $this->assertDatabaseMissing('membership_levels', ['id' => $level->id]);
    }

    public function test_option_validation_and_unique_names(): void
    {
        $this->signInAdmin();
        $this->post('/categories', ['name' => ''])->assertSessionHasErrors('name');
        $this->post('/categories', ['name' => 'Student'])->assertSessionHasErrors('name');
        $this->post('/levels', ['name' => 'Basic', 'fee' => -1, 'validity_months' => 0])->assertSessionHasErrors(['name', 'fee', 'validity_months']);
        $this->post('/levels', ['name' => 'Invalid', 'fee' => '12.345', 'validity_months' => '1.5'])->assertSessionHasErrors(['fee', 'validity_months']);
        $level = MembershipLevel::firstOrFail();
        $this->put('/levels/'.$level->id, ['name' => $level->name, 'fee' => 0, 'validity_months' => 1])->assertSessionHasNoErrors();
    }

    public function test_assigned_options_cannot_be_deleted_even_for_inactive_members(): void
    {
        $this->signInAdmin();
        $member = Member::register([
            'first_name' => 'Sample', 'last_name' => 'Member', 'email' => 'sample@example.test',
            'membership_category_id' => MembershipCategory::firstOrFail()->id,
            'membership_level_id' => MembershipLevel::firstOrFail()->id, 'status' => 'inactive',
        ]);
        $this->delete('/categories/'.$member->membership_category_id)->assertSessionHasErrors('category');
        $this->delete('/levels/'.$member->membership_level_id)->assertSessionHasErrors('level');
        $this->assertDatabaseHas('membership_categories', ['id' => $member->membership_category_id]);
        $this->assertDatabaseHas('membership_levels', ['id' => $member->membership_level_id]);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    public function test_new_options_appear_in_member_forms_and_assignment_works(): void
    {
        $this->signInAdmin();
        $category = MembershipCategory::create(['name' => 'Alumni']);
        $level = MembershipLevel::create(['name' => 'Diamond', 'fee' => 100, 'validity_months' => 12]);
        $this->get('/members/create')->assertOk()->assertSee('Alumni')->assertSee('Diamond');
        $this->post('/members', [
            'first_name' => 'Sample', 'last_name' => 'Member', 'email' => 'sample@example.test',
            'membership_category_id' => $category->id, 'membership_level_id' => $level->id, 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->get('/members/'.Member::firstOrFail()->id)->assertOk()->assertSee('Alumni')->assertSee('Diamond');
        $this->get('/categories/'.$category->id)->assertOk()->assertSee('1 member');
    }
}
