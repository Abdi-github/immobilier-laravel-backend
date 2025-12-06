<?php

declare(strict_types=1);

use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;

// ────────────────────────────────────────────────────────────────
// Properties
// ────────────────────────────────────────────────────────────────

describe('GET /api/v1/public/properties', function () {

    beforeEach(function () {
        $this->canton = CantonFactory::new()->create();
        $this->city = CityFactory::new()->create(['canton_id' => $this->canton->id]);
        $this->category = CategoryFactory::new()->create();
        $this->agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);
        $this->agent = UserFactory::new()->agent()->create(['agency_id' => $this->agency->id]);
    });

    it('lists published properties', function () {
        PropertyFactory::new()->count(3)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        // Draft properties should not appear
        PropertyFactory::new()->draft()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson('/api/v1/public/properties');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'limit', 'total', 'totalPages', 'hasNextPage', 'hasPrevPage'],
            ]);

        // Only published properties should be returned
        expect($response->json('meta.total'))->toBe(3);
    });

    it('shows a single property by id', function () {
        $property = PropertyFactory::new()->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson("/api/v1/public/properties/{$property->id}");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['_id', 'external_id', 'transaction_type', 'price', 'address', 'status'],
            ]);
    });

    it('shows a property by external_id', function () {
        $property = PropertyFactory::new()->published()->create([
            'external_id' => 'EXT-12345',
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson('/api/v1/public/properties/external/EXT-12345');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 404 for non-existent property', function () {
        $response = $this->getJson('/api/v1/public/properties/9999');

        $response->assertStatus(404);
    });

    it('lists properties by canton', function () {
        PropertyFactory::new()->count(2)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson("/api/v1/public/properties/canton/{$this->canton->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists properties by city', function () {
        PropertyFactory::new()->count(2)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson("/api/v1/public/properties/city/{$this->city->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists properties by agency', function () {
        PropertyFactory::new()->count(2)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson("/api/v1/public/properties/agency/{$this->agency->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists properties by category', function () {
        PropertyFactory::new()->count(2)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson("/api/v1/public/properties/category/{$this->category->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns cursor-paginated properties', function () {
        PropertyFactory::new()->count(5)->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->getJson('/api/v1/public/properties/cursor?limit=2');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['hasNextPage', 'hasPrevPage', 'nextCursor', 'prevCursor'],
            ]);
    });
});

// ────────────────────────────────────────────────────────────────
// Agencies
// ────────────────────────────────────────────────────────────────

describe('GET /api/v1/public/agencies', function () {

    it('lists agencies', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        AgencyFactory::new()->count(3)->create([
            'canton_id' => $canton->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson('/api/v1/public/agencies');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows a single agency by id', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        $agency = AgencyFactory::new()->create([
            'canton_id' => $canton->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/v1/public/agencies/{$agency->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows an agency by slug', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        $agency = AgencyFactory::new()->create([
            'name' => 'Test Realty',
            'canton_id' => $canton->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/v1/public/agencies/slug/{$agency->slug}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 404 for non-existent agency', function () {
        $response = $this->getJson('/api/v1/public/agencies/9999');

        $response->assertStatus(404);
    });

    it('lists agencies by canton', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        AgencyFactory::new()->count(2)->create([
            'canton_id' => $canton->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/v1/public/agencies/canton/{$canton->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists agencies by city', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        AgencyFactory::new()->count(2)->create([
            'canton_id' => $canton->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/v1/public/agencies/city/{$city->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});
