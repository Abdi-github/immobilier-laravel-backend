<?php

declare(strict_types=1);

use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;

// ────────────────────────────────────────────────────────────────
// Health Check
// ────────────────────────────────────────────────────────────────

describe('GET /api/v1/public', function () {

    it('returns welcome response', function () {
        $response = $this->getJson('/api/v1/public');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'version' => 'v1',
                    'status' => 'active',
                ],
            ]);
    });
});

// ────────────────────────────────────────────────────────────────
// Cantons
// ────────────────────────────────────────────────────────────────

describe('Cantons', function () {

    it('lists cantons', function () {
        CantonFactory::new()->count(3)->create();

        $response = $this->getJson('/api/v1/public/locations/cantons');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    });

    it('shows a single canton by id', function () {
        $canton = CantonFactory::new()->create(['code' => 'ZH']);

        $response = $this->getJson("/api/v1/public/locations/cantons/{$canton->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'ZH'],
            ]);
    });

    it('shows a canton by code', function () {
        CantonFactory::new()->create(['code' => 'GE']);

        $response = $this->getJson('/api/v1/public/locations/cantons/code/GE');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'GE'],
            ]);
    });

    it('returns 404 for non-existent canton', function () {
        $response = $this->getJson('/api/v1/public/locations/cantons/9999');

        $response->assertStatus(404);
    });

    it('lists cities for a canton', function () {
        $canton = CantonFactory::new()->create();
        CityFactory::new()->count(2)->create(['canton_id' => $canton->id]);

        $response = $this->getJson("/api/v1/public/locations/cantons/{$canton->id}/cities");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Cities
// ────────────────────────────────────────────────────────────────

describe('Cities', function () {

    it('lists cities', function () {
        $canton = CantonFactory::new()->create();
        CityFactory::new()->count(3)->create(['canton_id' => $canton->id]);

        $response = $this->getJson('/api/v1/public/locations/cities');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows a single city by id', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id, 'postal_code' => '1200']);

        $response = $this->getJson("/api/v1/public/locations/cities/{$city->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['postal_code' => '1200'],
            ]);
    });

    it('finds city by postal code', function () {
        $canton = CantonFactory::new()->create();
        CityFactory::new()->create(['canton_id' => $canton->id, 'postal_code' => '3000']);

        $response = $this->getJson('/api/v1/public/locations/cities/postal/3000');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 404 for non-existent city', function () {
        $response = $this->getJson('/api/v1/public/locations/cities/9999');

        $response->assertStatus(404);
    });
});

// ────────────────────────────────────────────────────────────────
// Categories
// ────────────────────────────────────────────────────────────────

describe('Categories', function () {

    it('lists categories', function () {
        CategoryFactory::new()->count(3)->create();

        $response = $this->getJson('/api/v1/public/categories');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    });

    it('shows a single category by id', function () {
        $category = CategoryFactory::new()->create();

        $response = $this->getJson("/api/v1/public/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows a category by slug', function () {
        $category = CategoryFactory::new()->create(['slug' => 'apartments']);

        $response = $this->getJson('/api/v1/public/categories/slug/apartments');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists categories by section', function () {
        CategoryFactory::new()->count(2)->create(['section' => 'residential']);
        CategoryFactory::new()->create(['section' => 'commercial']);

        $response = $this->getJson('/api/v1/public/categories/section/residential');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 404 for non-existent category', function () {
        $response = $this->getJson('/api/v1/public/categories/9999');

        $response->assertStatus(404);
    });
});
