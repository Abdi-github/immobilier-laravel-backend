<?php

declare(strict_types=1);

use App\Domain\Lead\Models\Lead;
use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;

// ────────────────────────────────────────────────────────────────
// Agency Lead Management
// ────────────────────────────────────────────────────────────────

describe('Agency Lead Management', function () {

    beforeEach(function () {
        $this->canton = CantonFactory::new()->create();
        $this->city = CityFactory::new()->create(['canton_id' => $this->canton->id]);
        $this->category = CategoryFactory::new()->create();
        $this->agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);
        $this->agencyAdmin = UserFactory::new()->agencyAdmin()->create(['agency_id' => $this->agency->id]);
        $this->agencyAdmin->assignRole('agency_admin');
        $this->agent = UserFactory::new()->agent()->create(['agency_id' => $this->agency->id]);
        $this->agent->assignRole('agent');
        $this->headers = loginAs($this->agencyAdmin);

        $this->property = PropertyFactory::new()->published()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        // Create a lead for the agency
        $this->lead = Lead::create([
            'property_id' => $this->property->id,
            'agency_id' => $this->agency->id,
            'contact_first_name' => 'Lead',
            'contact_last_name' => 'Contact',
            'contact_email' => 'lead@example.com',
            'message' => 'Test lead message',
            'inquiry_type' => 'general_inquiry',
            'status' => 'NEW',
        ]);
    });

    it('blocks unauthenticated access', function () {
        $response = $this->getJson('/api/v1/agency/leads');
        $response->assertStatus(401);
    });

    it('blocks users without agency membership', function () {
        $user = createUser(); // end_user without agency
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->getJson('/api/v1/agency/leads');
        $response->assertStatus(403);
    });

    it('lists agency leads', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/agency/leads');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta']);
    });

    it('shows a single lead', function () {
        $response = $this->withHeaders($this->headers)->getJson("/api/v1/agency/leads/{$this->lead->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates a lead', function () {
        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/agency/leads/{$this->lead->id}", [
            'priority' => 'high',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates lead status', function () {
        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/agency/leads/{$this->lead->id}/status", [
            'status' => 'CONTACTED',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('assigns a lead to an agent', function () {
        $response = $this->withHeaders($this->headers)->postJson("/api/v1/agency/leads/{$this->lead->id}/assign", [
            'assigned_to' => $this->agent->id,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('adds a note to a lead', function () {
        $response = $this->withHeaders($this->headers)->postJson("/api/v1/agency/leads/{$this->lead->id}/notes", [
            'content' => 'Contacted the client, scheduling a viewing.',
            'is_internal' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('lists lead notes', function () {
        // Add a note first
        $this->withHeaders($this->headers)->postJson("/api/v1/agency/leads/{$this->lead->id}/notes", [
            'content' => 'Test note',
            'is_internal' => true,
        ]);

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/agency/leads/{$this->lead->id}/notes");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns lead statistics', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/agency/leads/statistics');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Agent Property Management
// ────────────────────────────────────────────────────────────────

describe('Agent Property Management', function () {

    beforeEach(function () {
        $this->canton = CantonFactory::new()->create();
        $this->city = CityFactory::new()->create(['canton_id' => $this->canton->id]);
        $this->category = CategoryFactory::new()->create();
        $this->agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);
        $this->agent = UserFactory::new()->agent()->create(['agency_id' => $this->agency->id]);
        $this->agent->assignRole('agent');
        $this->headers = loginAs($this->agent);
    });

    it('lists agent own properties', function () {
        PropertyFactory::new()->count(2)->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/agent/properties');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('creates a property', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/agent/properties', [
            'external_id' => 'AGENT-PROP-001',
            'category_id' => $this->category->id,
            'transaction_type' => 'rent',
            'price' => 1800.00,
            'address' => '456 Agent Street',
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
            'postal_code' => '3000',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('shows own property', function () {
        $property = PropertyFactory::new()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/agent/properties/{$property->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates own property', function () {
        $property = PropertyFactory::new()->draft()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->putJson("/api/v1/agent/properties/{$property->id}", [
            'price' => 2200.00,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('submits own property for review', function () {
        $property = PropertyFactory::new()->draft()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/agent/properties/{$property->id}/submit");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes own property', function () {
        $property = PropertyFactory::new()->draft()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/agent/properties/{$property->id}");

        $response->assertOk();
    });
});

// ────────────────────────────────────────────────────────────────
// Agent Lead Access
// ────────────────────────────────────────────────────────────────

describe('Agent Lead Access', function () {

    it('lists assigned leads', function () {
        $agent = createAgent();
        $agent->assignRole('agent');
        $headers = loginAs($agent);

        $response = $this->withHeaders($headers)->getJson('/api/v1/agent/leads');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails for unauthenticated access', function () {
        $this->getJson('/api/v1/agent/leads')->assertStatus(401);
    });
});
