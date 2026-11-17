<?php

namespace Tests\Feature;

use App\Jobs\SendMembershipApplicationEmail;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Services\ApplicationEmail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class MembershipIntegrationsTest extends TestCase
{
    use DatabaseMigrations;

    private function member(): Member
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        MembershipLevel::firstOrFail()->update(['fee' => 5000, 'validity_months' => 12]);

        return Member::register($this->details());
    }

    private function details(array $overrides = []): array
    {
        return array_replace(['first_name' => 'Alex', 'last_name' => 'Rivera', 'email' => 'alex@example.test', 'membership_category_id' => MembershipCategory::firstOrFail()->id, 'membership_level_id' => MembershipLevel::firstOrFail()->id, 'status' => 'active'], $overrides);
    }

    private function application(Member $member, array $extra = []): MembershipTransaction
    {
        $this->post('/transactions', $extra + ['member_id' => $member->id, 'type' => 'Application'])->assertSessionHasNoErrors()->assertRedirect();

        return MembershipTransaction::latest('id')->firstOrFail();
    }

    public function test_location_is_optional_and_requires_a_valid_pair_and_consent_when_provided(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post('/members', $this->details(['latitude' => 14.5995]))->assertSessionHasErrors(['longitude', 'location_consent']);
        $this->post('/members', $this->details(['latitude' => 91, 'longitude' => 181, 'location_consent' => 1]))->assertSessionHasErrors(['latitude', 'longitude']);
        $this->post('/members', $this->details(['latitude' => null, 'longitude' => null]))->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $this->assertNull($member->latitude);
        $this->assertNull($member->location_consented_at);
        $this->put('/members/'.$member->id, $this->details(['latitude' => 14.5995, 'longitude' => 120.9842, 'location_consent' => 1]))->assertSessionHasNoErrors();
        $this->assertSame('14.5995000', $member->fresh()->latitude);
        $this->assertNotNull($member->fresh()->location_consented_at);
        $this->get('/members/'.$member->id)->assertOk()->assertSee('Saved location')->assertSee('14.5995000');
    }

    public function test_transaction_location_is_a_snapshot_and_never_changes_membership_or_payment_status(): void
    {
        $member = $this->member();
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Application', 'latitude' => 14])->assertSessionHasErrors(['longitude', 'location_consent']);
        $transaction = $this->application($member, ['latitude' => 0, 'longitude' => 0, 'location_consent' => 1]);
        $member->update(['latitude' => 15, 'longitude' => 121]);
        $this->assertSame('0.0000000', $transaction->fresh()->latitude);
        $this->assertSame('Pending', $transaction->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_weather_requires_admin_access_and_valid_coordinates(): void
    {
        Http::preventStrayRequests();
        $this->getJson('/weather?lat=14&lon=120')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/weather?lat=14&lon=120')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->getJson('/weather?lat=91&lon=181')->assertUnprocessable();
        $this->getJson('/weather?lat=14')->assertUnprocessable();
        $this->getJson('/weather?lat=14&lon=120')->assertStatus(503)->assertJson(['message' => 'Weather has not been configured yet.']);
        Http::assertNothingSent();
    }

    public function test_weather_uses_a_server_key_metric_units_and_cached_coarse_coordinates(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        config(['services.openweather.key' => 'server-only-test-key']);
        Http::fake(['api.openweathermap.org/*' => Http::response(['name' => 'Manila', 'main' => ['temp' => 30.5, 'humidity' => 70], 'weather' => [['description' => 'clear sky']]])]);
        $this->getJson('/weather?lat=14.59951&lon=120.98421')->assertOk()->assertJson(['place' => 'Manila', 'temperature' => 30.5])->assertDontSee('server-only-test-key');
        $this->getJson('/weather?lat=14.59952&lon=120.98422')->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['appid'] === 'server-only-test-key' && $request['units'] === 'metric' && $request['lat'] === '14.600');
        $this->get('/members/create')->assertOk()->assertDontSee('server-only-test-key');
    }

    public function test_weather_provider_errors_are_redacted_and_not_cached(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        config(['services.openweather.key' => 'secret-test-key']);
        Http::fakeSequence()->push(['message' => 'invalid secret-test-key'], 401)->push(['name' => 'Manila', 'main' => ['temp' => 29]]);
        $this->getJson('/weather?lat=0&lon=0')->assertStatus(503)->assertDontSee('secret-test-key');
        $this->getJson('/weather?lat=0&lon=0')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_unconfigured_email_does_not_block_application_or_send_anything(): void
    {
        Notification::fake();
        $transaction = $this->application($this->member());
        $this->assertSame('not_configured', $transaction->fresh()->application_email_status);
        $this->assertSame('Pending', $transaction->fresh()->status);
        Notification::assertNothingSent();
        $this->get('/transactions/'.$transaction->id)->assertOk()->assertSee('Email sending has not been configured yet.');
    }

    public function test_application_email_only_runs_after_commit_and_is_not_resent(): void
    {
        Notification::fake();
        config(['services.membership_mail.enabled' => true]);
        $member = $this->member();
        DB::beginTransaction();
        $transaction = $this->application($member);
        Notification::assertNothingSent();
        DB::commit();
        Notification::assertSentOnDemand(MembershipApplicationSubmitted::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $member->email);
        $this->assertSame('sent', $transaction->fresh()->application_email_status);
        app(ApplicationEmail::class)->dispatch($transaction->id);
        Notification::assertCount(1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('Pending', $transaction->fresh()->status);
    }

    public function test_rollback_does_not_send_or_queue_an_email(): void
    {
        Notification::fake();
        config(['services.membership_mail.enabled' => true]);
        $member = $this->member();
        DB::beginTransaction();
        $this->application($member);
        DB::rollBack();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('membership_transactions', 0);
    }

    public function test_smtp_failure_preserves_transaction_and_can_be_retried(): void
    {
        config(['services.membership_mail.enabled' => true]);
        $member = $this->member();
        Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('Mail provider unavailable'));
        $transaction = $this->application($member);
        $this->assertSame('failed', $transaction->fresh()->application_email_status);
        $this->assertSame('Pending', $transaction->fresh()->status);
        Notification::fake();
        $this->post('/transactions/'.$transaction->id.'/application-email')->assertSessionHasNoErrors();
        Notification::assertCount(1);
        $this->assertSame('sent', $transaction->fresh()->application_email_status);
    }

    public function test_database_queue_is_supported_without_marking_an_email_sent_early(): void
    {
        Queue::fake();
        config(['services.membership_mail.enabled' => true, 'services.membership_mail.connection' => 'database']);
        $transaction = $this->application($this->member());
        Queue::assertPushed(SendMembershipApplicationEmail::class, fn ($job) => $job->transactionId === $transaction->id && $job->connection === 'database');
        $this->assertSame('queued', $transaction->fresh()->application_email_status);
        $this->assertNull($transaction->fresh()->application_email_sent_at);
    }

    public function test_retry_and_weather_endpoints_are_rate_limited(): void
    {
        $transaction = $this->application($this->member());
        for ($i = 0; $i < 3; $i++) {
            $this->post('/transactions/'.$transaction->id.'/application-email')->assertRedirect();
        }
        $this->post('/transactions/'.$transaction->id.'/application-email')->assertTooManyRequests();
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/weather?lat=0&lon=0')->assertStatus(503);
        }
        $this->getJson('/weather?lat=0&lon=0')->assertTooManyRequests();
    }
}
