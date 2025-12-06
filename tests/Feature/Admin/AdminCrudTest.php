<?php

declare(strict_types=1);

use Database\Factories\AgencyFactory;
use Database\Factories\CantonFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CityFactory;
use Database\Factories\PropertyFactory;
use Database\Factories\UserFactory;
use Spatie\Permission\Models\Permission;

// ────────────────────────────────────────────────────────────────
// Admin Auth & Access Control
// ────────────────────────────────────────────────────────────────

describe('Admin Access Control', function () {

    it('blocks unauthenticated access', function () {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(401);
    });

    it('blocks non-admin users', function () {
        $user = createUser(); // end_user type
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    });

    it('allows super_admin access', function () {
        $admin = createAdmin();
        $admin->assignRole('super_admin');
        $headers = loginAs($admin);

        $response = $this->withHeaders($headers)->getJson('/api/v1/admin/');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Users
// ────────────────────────────────────────────────────────────────

describe('Admin Users CRUD', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
    });

    it('lists users', function () {
        UserFactory::new()->count(3)->create();

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/users');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta']);
    });

    it('shows a single user', function () {
        $user = createUser();

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['email' => $user->email],
            ]);
    });

    it('creates a new user', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/users', [
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'first_name' => 'New',
            'last_name' => 'User',
            'user_type' => 'end_user',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    });

    it('updates a user', function () {
        $user = createUser();

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/users/{$user->id}", [
            'first_name' => 'UpdatedName',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes a user', function () {
        $user = createUser();

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/admin/users/{$user->id}");

        $response->assertOk();
    });

    it('suspends a user', function () {
        $user = createUser();

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/users/{$user->id}/suspend");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('activates a user', function () {
        $user = UserFactory::new()->suspended()->create();

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/users/{$user->id}/activate");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns user statistics', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/users/statistics');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 404 for non-existent user', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/users/99999');

        $response->assertStatus(404);
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Agencies
// ────────────────────────────────────────────────────────────────

describe('Admin Agencies CRUD', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
        $this->canton = CantonFactory::new()->create();
        $this->city = CityFactory::new()->create(['canton_id' => $this->canton->id]);
    });

    it('lists agencies', function () {
        AgencyFactory::new()->count(3)->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/agencies');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('creates a new agency', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/agencies', [
            'name' => 'Test Agency',
            'email' => 'agency@test.com',
            'phone' => '+41 22 000 00 00',
            'address' => '123 Test Street',
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
            'postal_code' => '1200',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('shows a single agency', function () {
        $agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/admin/agencies/{$agency->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('updates an agency', function () {
        $agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/agencies/{$agency->id}", [
            'name' => 'Updated Agency Name',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('verifies an agency', function () {
        $agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
            'is_verified' => false,
        ]);

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/agencies/{$agency->id}/verify");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes an agency', function () {
        $agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/admin/agencies/{$agency->id}");

        $response->assertOk();
    });

    it('returns agency statistics', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/agencies/statistics');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Properties
// ────────────────────────────────────────────────────────────────

describe('Admin Properties CRUD', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
        $this->canton = CantonFactory::new()->create();
        $this->city = CityFactory::new()->create(['canton_id' => $this->canton->id]);
        $this->category = CategoryFactory::new()->create();
        $this->agency = AgencyFactory::new()->create([
            'canton_id' => $this->canton->id,
            'city_id' => $this->city->id,
        ]);
        $this->agent = UserFactory::new()->agent()->create(['agency_id' => $this->agency->id]);
    });

    it('lists properties', function () {
        PropertyFactory::new()->count(3)->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/properties');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows a single property', function () {
        $property = PropertyFactory::new()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/admin/properties/{$property->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('creates a property', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/properties', [
            'external_id' => 'ADMIN-PROP-001',
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'transaction_type' => 'rent',
            'price' => 2500.00,
            'address' => '123 Admin Street',
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
            'postal_code' => '1200',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('updates a property', function () {
        $property = PropertyFactory::new()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/properties/{$property->id}", [
            'price' => 3000.00,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('submits a property for review', function () {
        $property = PropertyFactory::new()->draft()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/properties/{$property->id}/submit");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('approves a pending property', function () {
        $property = PropertyFactory::new()->pending()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/properties/{$property->id}/approve");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('rejects a pending property', function () {
        $property = PropertyFactory::new()->pending()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->postJson("/api/v1/admin/properties/{$property->id}/reject", [
            'rejection_reason' => 'Missing property description',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes a property', function () {
        $property = PropertyFactory::new()->create([
            'category_id' => $this->category->id,
            'agency_id' => $this->agency->id,
            'owner_id' => $this->agent->id,
            'city_id' => $this->city->id,
            'canton_id' => $this->canton->id,
        ]);

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/admin/properties/{$property->id}");

        $response->assertOk();
    });

    it('returns property statistics', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/properties/statistics');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Categories & Amenities
// ────────────────────────────────────────────────────────────────

describe('Admin Categories', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
    });

    it('creates a category', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/categories', [
            'section' => 'residential',
            'name' => ['en' => 'Apartments', 'fr' => 'Appartements'],
            'slug' => 'test-apartments',
            'icon' => 'building',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('updates a category', function () {
        $category = CategoryFactory::new()->create();

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/categories/{$category->id}", [
            'icon' => 'house',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('deletes a category', function () {
        $category = CategoryFactory::new()->create();

        $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertOk();
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Locations
// ────────────────────────────────────────────────────────────────

describe('Admin Locations', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
    });

    it('creates a canton', function () {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/locations/cantons', [
            'code' => 'TG',
            'name' => ['en' => 'Thurgau', 'fr' => 'Thurgovie', 'de' => 'Thurgau', 'it' => 'Turgovia'],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('updates a canton', function () {
        $canton = CantonFactory::new()->create();

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/locations/cantons/{$canton->id}", [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('creates a city', function () {
        $canton = CantonFactory::new()->create();

        $response = $this->withHeaders($this->headers)->postJson('/api/v1/admin/locations/cities', [
            'canton_id' => $canton->id,
            'name' => ['en' => 'Zurich', 'fr' => 'Zurich', 'de' => 'Zürich', 'it' => 'Zurigo'],
            'postal_code' => '8000',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    });

    it('updates a city', function () {
        $canton = CantonFactory::new()->create();
        $city = CityFactory::new()->create(['canton_id' => $canton->id]);

        $response = $this->withHeaders($this->headers)->patchJson("/api/v1/admin/locations/cities/{$city->id}", [
            'postal_code' => '8001',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ────────────────────────────────────────────────────────────────
// Admin Roles & Permissions
// ────────────────────────────────────────────────────────────────

describe('Admin Roles & Permissions', function () {

    beforeEach(function () {
        $this->admin = createAdmin();
        $this->admin->assignRole('super_admin');
        $this->headers = loginAs($this->admin);
    });

    it('lists roles', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/roles');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows a role', function () {
        $role = \Spatie\Permission\Models\Role::where('name', 'agent')->first();

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/admin/roles/{$role->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('lists permissions', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/permissions');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows active permissions', function () {
        $response = $this->withHeaders($this->headers)->getJson('/api/v1/admin/permissions/active');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('shows role permissions', function () {
        $role = \Spatie\Permission\Models\Role::where('name', 'agent')->first();

        $response = $this->withHeaders($this->headers)->getJson("/api/v1/admin/roles/{$role->id}/permissions");

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});
