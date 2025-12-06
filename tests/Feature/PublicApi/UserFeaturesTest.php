<?php

declare(strict_types=1);

use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;

// ────────────────────────────────────────────────────────────────
// User Dashboard
// ────────────────────────────────────────────────────────────────

describe('User Dashboard', function () {

    it('returns dashboard stats for authenticated user', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->getJson('/api/v1/public/users/dashboard/stats');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails for unauthenticated user', function () {
        $response = $this->getJson('/api/v1/public/users/dashboard/stats');

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Favorites
// ────────────────────────────────────────────────────────────────

describe('Favorites', function () {

    beforeEach(function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);
        $category = CategoryFactory::new()->create();
        $agency = AgencyFactory::new()->create(['canton_id' => $canton->id, 'city_id' => $city->id]);
        $agent = UserFactory::new()->agent()->create(['agency_id' => $agency->id]);

        $this->property = PropertyFactory::new()->published()->create([
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'owner_id' => $agent->id,
            'city_id' => $city->id,
            'canton_id' => $canton->id,
        ]);
        $this->user = createUser();
        $this->headers = loginAs($this->user);
    });

    it('adds a property to favorites', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/public/users/favorites', [
            'property_id' => $this->property->id,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
        ]);
    });

    it('lists favorites', function () {
        // Add favorite first
        $this->withHeaders($this->headers)->postJson('/api/v1/public/users/favorites', [
            'property_id' => $this->property->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/public/users/favorites');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('checks if property is favorited', function () {
        $this->withHeaders($this->headers)->postJson('/api/v1/public/users/favorites', [
            'property_id' => $this->property->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/public/users/favorites/{$this->property->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('removes a property from favorites', function () {
        $this->withHeaders($this->headers)->postJson('/api/v1/public/users/favorites', [
            'property_id' => $this->property->id,
        ]);

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/public/users/favorites/{$this->property->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
        ]);
    });

    it('returns favorite ids', function () {
        $this->withHeaders($this->headers)->postJson('/api/v1/public/users/favorites', [
            'property_id' => $this->property->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/public/users/favorites/ids');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Alerts
// ────────────────────────────────────────────────────────────────

describe('Alerts', function () {

    beforeEach(function () {
        $this->user = createUser();
        $this->headers = loginAs($this->user);
    });

    it('creates an alert', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/public/users/alerts', [
            'name' => 'New apartments in Zurich',
            'criteria' => ['canton_id' => 1, 'transaction_type' => 'rent', 'max_price' => 3000],
            'frequency' => 'daily',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('lists alerts', function () {
        $this->withHeaders($this->headers)->postJson('/api/v1/public/users/alerts', [
            'name' => 'Test Alert',
            'criteria' => ['transaction_type' => 'buy'],
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/public/users/alerts');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes an alert', function () {
        $createResponse = $this->withHeaders($this->headers)->postJson('/api/v1/public/users/alerts', [
            'name' => 'Delete Me',
            'criteria' => ['transaction_type' => 'rent'],
        ]);

        $alertId = $createResponse->json('data._id') ?? $createResponse->json('data.id');

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/public/users/alerts/{$alertId}");

        $response->assertOk();
    });

    it('fails for unauthenticated access', function () {
        $response = $this->getJson('/api/v1/public/users/alerts');

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Settings & Profile
// ────────────────────────────────────────────────────────────────

describe('User Settings & Profile', function () {

    beforeEach(function () {
        $this->user = createUser();
        $this->headers = loginAs($this->user);
    });

    it('gets user settings', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/public/users/settings');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates user settings', function () {
        $response = $this->withHeaders($this->headers)->patchJson('/api/v1/public/users/settings', [
            'preferred_language' => 'fr',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('gets user profile', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/public/users/profile');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates user profile', function () {
        $response = $this->withHeaders($this->headers)->putJson('/api/v1/public/users/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Profile',
            'phone' => '+41 79 000 00 00',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});
