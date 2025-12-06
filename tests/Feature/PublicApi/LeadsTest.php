<?php

declare(strict_types=1);

use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;

// ────────────────────────────────────────────────────────────────
// Public Lead Submission
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/leads', function () {

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
    });

    it('creates a lead for a published property', function () {
        $response = $this->postJson('/api/v1/public/leads', [
            'property_id' => $this->property->id,
            'contact_first_name' => 'Interested',
            'contact_last_name' => 'Buyer',
            'contact_email' => 'buyer@example.com',
            'message' => 'I am interested in this property. Is it still available?',
            'inquiry_type' => 'general_inquiry',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('leads', [
            'property_id' => $this->property->id,
            'contact_email' => 'buyer@example.com',
        ]);
    });

    it('fails with missing required fields', function () {
        $response = $this->postJson('/api/v1/public/leads', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    });

    it('fails with invalid property id', function () {
        $response = $this->postJson('/api/v1/public/leads', [
            'property_id' => 9999,
            'contact_first_name' => 'Buyer',
            'contact_last_name' => 'Test',
            'contact_email' => 'buyer@example.com',
            'message' => 'Test message',
            'inquiry_type' => 'general_inquiry',
        ]);

        $response->assertStatus(422);
    });

    it('fails with invalid inquiry type', function () {
        $response = $this->postJson('/api/v1/public/leads', [
            'property_id' => $this->property->id,
            'contact_first_name' => 'Buyer',
            'contact_last_name' => 'Test',
            'contact_email' => 'buyer@example.com',
            'message' => 'Test message',
            'inquiry_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
    });
});

// ────────────────────────────────────────────────────────────────
// Authenticated Lead Submission
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/leads/authenticated', function () {

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
    });

    it('creates a lead for authenticated user', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/public/leads/authenticated', [
            'property_id' => $this->property->id,
            'message' => 'I am interested in this property',
            'inquiry_type' => 'viewing_request',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('fails for unauthenticated user', function () {
        $response = $this->postJson('/api/v1/public/leads/authenticated', [
            'property_id' => $this->property->id,
            'message' => 'Test',
            'inquiry_type' => 'general_inquiry',
        ]);

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// My Inquiries
// ────────────────────────────────────────────────────────────────

describe('GET /api/v1/public/leads/my-inquiries', function () {

    it('returns user inquiries', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->getJson('/api/v1/public/leads/my-inquiries');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta']);
    });

    it('fails for unauthenticated user', function () {
        $response = $this->getJson('/api/v1/public/leads/my-inquiries');

        $response->assertStatus(401);
    });
});
