<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Filament\Resources\InquiryResource\InquiryActions;
use App\Filament\Resources\InquiryResource\Pages\CreateInquiry;
use App\Filament\Resources\InquiryResource\Pages\ViewInquiry;
use App\Filament\Resources\InquiryResource\RelationManagers\TimelineRelationManager;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Custom tour inquiries: website endpoint (POST /journey-requests) + admin inbox. */
final class InquiryTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/journey-requests';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        // Many requests come from one test IP; only the rate-limit test lowers this again.
        config(['inquiries.rate_limit' => ['per_minute' => 1000, 'per_day' => 1000]]);
    }

    /** A complete, valid request exactly as the website sends it. */
    private function payload(array $overrides = []): array
    {
        $arrival = now()->addMonth()->startOfDay();

        return array_merge([
            'arrival_date' => $arrival->toDateString(),
            'arrival_time' => '09:30',
            'departure_date' => $arrival->copy()->addDays(10)->toDateString(),
            'flexible_dates' => true,
            'arrival_airport' => 'cmb',
            'occasion' => 'family',
            'pace' => 'moderate',
            'men' => 1,
            'women' => 1,
            'children' => 2,
            'child_ages' => [6, 0],
            'accommodation' => 'comfort',
            'rooms' => 2,
            'meal_plan' => 'hb',
            'vehicle' => 'van',
            'guide_language' => 'German',
            'budget' => '1000-2000',
            'dietary' => 'One vegetarian',
            'accessibility' => null,
            'destination_ids' => [],
            'activity_ids' => [],
            'notes' => 'A night in a treehouse, please.',
            'name' => 'Anna Weber',
            'email' => 'Anna.Weber@Example.com',
            'phone' => '+49 151 2345678',
            'country' => 'Germany',
            'contact_method' => 'whatsapp',
            'referral' => 'Instagram',
            'website' => '',
        ], $overrides);
    }

    // ---------------------------------------------------------------- website endpoint

    public function test_activities_endpoint_lists_active_activities_only(): void
    {
        Activity::factory()->create(['name' => 'Whale watching']);
        Activity::factory()->create(['name' => 'Hidden one', 'is_active' => false]);

        $this->getJson('/api/v1/activities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Whale watching')
            ->assertJsonStructure(['data' => [['id', 'slug', 'name']]]);
    }

    public function test_a_valid_request_saves_the_guest_and_the_inquiry_with_its_picks_in_order(): void
    {
        [$sigiriya, $ella] = Destination::factory()->count(2)->create();
        [$safari, $train] = Activity::factory()->count(2)->create();

        $response = $this->postJson(self::URL, $this->payload([
            'destination_ids' => [$ella->id, $sigiriya->id],
            'activity_ids' => [$train->id, $safari->id],
        ]))->assertCreated();

        $inquiry = Inquiry::with(['guest', 'destinations', 'activities'])->sole();
        $response->assertJsonPath('data.reference', $inquiry->reference);
        $this->assertMatchesRegularExpression('/^LG-\d{4}-\d{5}$/', $inquiry->reference);

        $this->assertSame('anna.weber@example.com', $inquiry->guest->email);
        $this->assertSame('Anna Weber', $inquiry->guest->name);
        $this->assertSame('whatsapp', $inquiry->guest->preferred_contact);

        $this->assertSame(InquiryStatus::New, $inquiry->status);
        $this->assertSame(InquirySource::Website, $inquiry->source);
        $this->assertSame(10, $inquiry->nights());
        $this->assertSame(4, $inquiry->guestCount());
        $this->assertSame([6, 0], $inquiry->child_ages);
        $this->assertSame('1 man, 1 woman, 2 children (6, under 1)', $inquiry->travellersLabel());
        $this->assertSame([$ella->id, $sigiriya->id], $inquiry->destinations->pluck('id')->all());
        $this->assertSame([$train->id, $safari->id], $inquiry->activities->pluck('id')->all());
        $this->assertSame('127.0.0.1', $inquiry->ip_address);
    }

    public function test_a_returning_guest_is_recognised_by_email_and_their_details_are_updated(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();
        $this->postJson(self::URL, $this->payload(['email' => ' ANNA.weber@example.com', 'phone' => '+49 170 9999999']))->assertCreated();

        $this->assertSame(1, Guest::count());
        $this->assertSame(2, Inquiry::count());
        $this->assertSame('+49 170 9999999', Guest::sole()->phone);
    }

    public function test_a_deleted_guest_who_writes_again_is_restored(): void
    {
        $guest = Guest::factory()->create(['email' => 'anna.weber@example.com']);
        $guest->delete();

        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertFalse($guest->fresh()->trashed());
        $this->assertSame(1, Guest::withTrashed()->count());
    }

    public function test_every_field_is_validated_on_the_server(): void
    {
        $draft = Destination::factory()->draft()->create();
        $inactive = Activity::factory()->create(['is_active' => false]);

        $cases = [
            'required fields' => [['name' => '', 'email' => '', 'phone' => '', 'country' => '', 'arrival_date' => '', 'departure_date' => ''],
                ['name', 'email', 'phone', 'country', 'arrival_date', 'departure_date']],
            'past arrival' => [['arrival_date' => now()->subDay()->toDateString()], ['arrival_date']],
            'too far ahead' => [['arrival_date' => now()->addYears(3)->toDateString(), 'departure_date' => now()->addYears(3)->addDays(5)->toDateString()], ['arrival_date']],
            'departure before arrival' => [['departure_date' => now()->addMonth()->subDay()->toDateString()], ['departure_date']],
            'trip too long' => [['departure_date' => now()->addMonth()->addDays(120)->toDateString()], ['departure_date']],
            'bad formats' => [['arrival_date' => '15/03/2027', 'arrival_time' => '9am', 'email' => 'anna@', 'phone' => 'call me'], ['arrival_date', 'arrival_time', 'email', 'phone']],
            'phone too short' => [['phone' => '+1 23'], ['phone']],
            'no adults' => [['men' => 0, 'women' => 0], ['men']],
            'too many guests' => [['men' => 31], ['men']],
            'ages do not match children' => [['children' => 2, 'child_ages' => [6]], ['child_ages']],
            'child age out of range' => [['child_ages' => [6, 18]], ['child_ages.1']],
            'rooms needed with hotels' => [['rooms' => null], ['rooms']],
            'unknown options' => [['pace' => 'balanced', 'arrival_airport' => 'lhr', 'accommodation' => 'castle', 'vehicle' => 'boat',
                'guide_language' => 'Klingon', 'budget' => 'a lot', 'contact_method' => 'pigeon', 'referral' => 'Radio', 'occasion' => 'party'],
                ['pace', 'arrival_airport', 'accommodation', 'vehicle', 'guide_language', 'budget', 'contact_method', 'referral', 'occasion']],
            'hidden places and activities' => [['destination_ids' => [$draft->id, 999999], 'activity_ids' => [$inactive->id]],
                ['destination_ids.0', 'destination_ids.1', 'activity_ids.0']],
            'too long' => [['notes' => str_repeat('a', 3001), 'dietary' => str_repeat('a', 301), 'name' => str_repeat('a', 121)], ['notes', 'dietary', 'name']],
        ];

        foreach ($cases as $case => [$overrides, $errors]) {
            $this->postJson(self::URL, $this->payload($overrides))
                ->assertUnprocessable()
                ->assertJsonValidationErrors($errors, 'errors', "Case: {$case}");
        }

        $this->assertSame(0, Inquiry::count());
        $this->assertSame(0, Guest::count());
    }

    public function test_no_hotel_needs_no_rooms_or_meals(): void
    {
        $this->postJson(self::URL, $this->payload(['accommodation' => 'none', 'rooms' => null, 'meal_plan' => null]))->assertCreated();

        $inquiry = Inquiry::sole();
        $this->assertNull($inquiry->rooms);
        $this->assertNull($inquiry->meal_plan);
    }

    public function test_html_is_stripped_from_free_text(): void
    {
        $this->postJson(self::URL, $this->payload([
            'name' => 'Anna <b>Weber</b>',
            'notes' => '<script>alert(1)</script>Beach days please',
        ]))->assertCreated();

        $this->assertSame('Anna Weber', Guest::sole()->name);
        $this->assertSame('alert(1)Beach days please', Inquiry::sole()->notes);
    }

    public function test_honeypot_requests_look_successful_but_save_nothing(): void
    {
        $this->postJson(self::URL, $this->payload(['website' => 'http://spam.example']))
            ->assertCreated()
            ->assertJsonPath('data.reference', null);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_requests_are_rate_limited_per_ip(): void
    {
        config(['inquiries.rate_limit.per_minute' => 2]); // the limit is read on every request

        $this->postJson(self::URL, $this->payload())->assertCreated();
        $this->postJson(self::URL, $this->payload())->assertCreated();
        $this->postJson(self::URL, $this->payload())->assertTooManyRequests();
    }

    // ---------------------------------------------------------------- admin

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    public function test_inquiry_managers_can_work_the_inbox_and_content_editors_cannot(): void
    {
        $inquiry = Inquiry::factory()->create();
        $admin = '/'.config('admin.path');

        $this->actingAsRole(Permissions::INQUIRY_MANAGER);
        foreach (['/inquiries', '/inquiries/create', "/inquiries/{$inquiry->id}", "/inquiries/{$inquiry->id}/edit", '/guests', "/guests/{$inquiry->guest_id}/edit"] as $page) {
            $this->get($admin.$page)->assertOk();
        }
        $this->get("{$admin}/inquiries/{$inquiry->id}")
            ->assertSee($inquiry->reference)
            ->assertSee($inquiry->guest->email)
            ->assertSee('Timeline');

        $this->actingAsRole(Permissions::CONTENT_EDITOR);
        $this->get("{$admin}/inquiries")->assertForbidden();
        $this->get("{$admin}/guests")->assertForbidden();
    }

    public function test_status_changes_are_logged_on_the_timeline_and_close_the_inquiry(): void
    {
        $user = $this->actingAsRole(Permissions::INQUIRY_MANAGER);
        $inquiry = Inquiry::factory()->create();

        InquiryActions::changeStatus($inquiry, ['status' => 'quoted', 'quoted_amount' => '2450', 'note' => 'Sent a 10-day plan.']);
        InquiryActions::changeStatus($inquiry->fresh(), ['status' => 'booked', 'note' => null]);
        InquiryActions::changeStatus($inquiry->fresh(), ['status' => 'booked', 'note' => 'Deposit received.']);

        $inquiry->refresh();
        $this->assertSame(InquiryStatus::Booked, $inquiry->status);
        $this->assertSame('2450.00', $inquiry->quoted_amount);
        $this->assertNotNull($inquiry->closed_at);

        $rows = $inquiry->timeline()->get();
        $this->assertCount(3, $rows);
        $this->assertSame('Deposit received.', $rows[0]->body);
        $this->assertNull($rows[0]->status_to);                       // same status: note only
        $this->assertSame(InquiryStatus::Booked, $rows[1]->status_to);
        $this->assertSame(InquiryStatus::New, $rows[2]->status_from);
        $this->assertSame('Sent a 10-day plan.', $rows[2]->body);
        $this->assertSame($user->id, $rows[2]->user_id);

        InquiryActions::changeStatus($inquiry, ['status' => 'contacted']);
        $this->assertNull($inquiry->fresh()->closed_at);               // reopened
    }

    public function test_change_status_action_on_the_inquiry_page(): void
    {
        $this->actingAsRole(Permissions::INQUIRY_MANAGER);
        $inquiry = Inquiry::factory()->create();

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->callAction('changeStatus', ['status' => 'contacted', 'note' => str_repeat('x', 2001)])
            ->assertHasActionErrors(['note'])
            ->callAction('changeStatus', ['status' => 'contacted', 'note' => 'Called, will send a plan.'])
            ->assertHasNoActionErrors();

        $this->assertSame(InquiryStatus::Contacted, $inquiry->fresh()->status);
        $this->assertSame('Called, will send a plan.', $inquiry->timeline()->first()->body);
    }

    public function test_timeline_lists_changes_and_staff_can_add_notes(): void
    {
        $user = $this->actingAsRole(Permissions::INQUIRY_MANAGER);
        $inquiry = Inquiry::factory()->create();
        InquiryActions::changeStatus($inquiry, ['status' => 'contacted', 'note' => 'Called the guest.']);

        Livewire::test(TimelineRelationManager::class, ['ownerRecord' => $inquiry, 'pageClass' => ViewInquiry::class])
            ->assertSuccessful()
            ->assertSee('Called the guest.')
            ->assertSee('New → Contacted')
            ->callTableAction('create', data: ['body' => ''])
            ->assertHasTableActionErrors(['body' => 'required'])
            ->callTableAction('create', data: ['body' => 'Sent the itinerary.'])
            ->assertHasNoTableActionErrors();

        $latest = $inquiry->timeline()->first();
        $this->assertSame('Sent the itinerary.', $latest->body);
        $this->assertSame($user->id, $latest->user_id);
    }

    public function test_admin_create_form_validates_on_the_server_and_saves_picks(): void
    {
        $this->actingAsRole(Permissions::INQUIRY_MANAGER);
        Repeater::fake();
        $guest = Guest::factory()->create();
        [$a, $b] = Destination::factory()->count(2)->create();

        Livewire::test(CreateInquiry::class)
            ->fillForm([
                'guest_id' => null, 'arrival_date' => '2027-03-10', 'departure_date' => '2027-03-09',
                'men' => 0, 'women' => 0, 'children' => 1, 'child_ages' => [], 'quoted_amount' => -5,
            ])
            ->call('create')
            ->assertHasFormErrors(['guest_id' => 'required', 'departure_date', 'men', 'child_ages', 'quoted_amount']);

        Livewire::test(CreateInquiry::class)
            ->fillForm([
                'guest_id' => $guest->id, 'source' => 'phone', 'status' => 'new',
                'arrival_date' => '2027-03-10', 'departure_date' => '2027-03-20',
                'men' => 2, 'women' => 0, 'children' => 1, 'child_ages' => [['age' => 9]], // simple repeater items, as Filament holds them
                'accommodation' => 'luxury', 'rooms' => 2, 'meal_plan' => 'bb', 'vehicle' => 'van',
                'guide_language' => 'English', 'pace' => 'active',
                'destination_ids' => [$b->id, $a->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $inquiry = Inquiry::with('destinations')->sole();
        $this->assertSame(InquirySource::Phone, $inquiry->source);
        $this->assertSame([9], $inquiry->child_ages);
        $this->assertSame([$b->id, $a->id], $inquiry->destinations->pluck('id')->all());
    }
}
