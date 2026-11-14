<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Destination;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_destinations_list_matches_the_frontend_contract(): void
    {
        $theme = Theme::factory()->create(['title' => 'Popular Beaches', 'slug' => 'beaches']);
        $point = Destination::factory()->create(['name' => 'Mirissa Beach', 'latitude' => 5.9467, 'longitude' => 80.4686]);
        $point->themes()->attach($theme);

        $this->getJson('/api/v1/destinations')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Mirissa Beach')
            ->assertJsonPath('data.0.lat', 5.9467)
            ->assertJsonPath('data.0.lng', 80.4686)
            ->assertJsonPath('data.0.themes', ['beaches'])
            ->assertJsonPath('data.0.city.slug', $point->city->slug)
            ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'category', 'lat', 'lng', 'themes', 'featured', 'description', 'image', 'city']]]);
    }

    public function test_only_published_points_in_active_cities_are_public(): void
    {
        Destination::factory()->create(['name' => 'Visible']);
        Destination::factory()->draft()->create(['name' => 'Draft one']);
        Destination::factory()->for(City::factory()->inactive())->create(['name' => 'In hidden city']);
        Destination::factory()->create(['name' => 'Trashed'])->delete();

        $names = collect($this->getJson('/api/v1/destinations')->assertOk()->json('data'))->pluck('name');

        $this->assertSame(['Visible'], $names->all());
    }

    public function test_filters_by_theme_city_and_featured(): void
    {
        $wildlife = Theme::factory()->create(['slug' => 'wildlife']);
        $yala = Destination::factory()->create(['name' => 'Yala', 'is_featured' => true]);
        $yala->themes()->attach($wildlife);
        Destination::factory()->create(['name' => 'Galle Fort']);

        $this->getJson('/api/v1/destinations?theme=wildlife')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Yala');
        $this->getJson('/api/v1/destinations?featured=1')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/destinations?city='.$yala->city->slug)->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/destinations?theme=<script>')->assertUnprocessable();
    }

    public function test_destination_detail_and_404s(): void
    {
        $point = Destination::factory()->create(['opening_hours' => 'Daily 06:00–18:00']);
        $draft = Destination::factory()->draft()->create();

        $this->getJson("/api/v1/destinations/{$point->slug}")
            ->assertOk()
            ->assertJsonPath('data.openingHours', 'Daily 06:00–18:00')
            ->assertJsonStructure(['data' => ['gallery', 'content', 'district', 'province']]);

        $this->getJson("/api/v1/destinations/{$draft->slug}")->assertNotFound();
        $this->getJson('/api/v1/destinations/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_city_endpoints_group_points_by_city(): void
    {
        $city = City::factory()->create(['name' => 'Anuradhapura']);
        Destination::factory()->for($city)->count(2)->create();
        Destination::factory()->for($city)->draft()->create();

        $this->getJson('/api/v1/cities')->assertOk()->assertJsonPath('data.0.destinationsCount', 2);
        $this->getJson('/api/v1/cities/anuradhapura')->assertOk()->assertJsonCount(2, 'data.destinations');
    }

    public function test_themes_endpoint(): void
    {
        Theme::factory()->create(['title' => 'Adventure', 'sort_order' => 2]);
        Theme::factory()->create(['title' => 'Beaches', 'sort_order' => 1]);
        Theme::factory()->create(['title' => 'Hidden', 'is_active' => false]);

        $this->getJson('/api/v1/themes')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.title', 'Beaches');
    }

    public function test_api_is_read_only_and_rate_limited(): void
    {
        $this->postJson('/api/v1/destinations', ['name' => 'x'])->assertMethodNotAllowed();
        $this->deleteJson('/api/v1/themes')->assertMethodNotAllowed();

        $this->getJson('/api/v1/themes')->assertHeader('X-RateLimit-Limit', '60')->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_stored_html_is_sanitized(): void
    {
        $point = Destination::factory()->create([
            'name' => '<b>Ravana</b> Falls',
            'summary' => 'Nice <script>alert(1)</script>waterfall in Ella',
            'description' => '<p onclick="x()">Hello <script>alert(1)</script><strong>world</strong><img src=x onerror=y></p>',
        ]);

        $this->assertSame('Ravana Falls', $point->name);
        $this->assertStringNotContainsString('<script', $point->summary);
        $this->assertSame('<p>Hello <strong>world</strong></p>', $point->description);
    }
}
