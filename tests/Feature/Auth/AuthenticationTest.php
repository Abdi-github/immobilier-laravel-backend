<?php

declare(strict_types=1);

use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

// ────────────────────────────────────────────────────────────────
// Registration
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/register', function () {

    it('registers a new user successfully', function () {
        $response = $this->postJson('/api/v1/public/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['_id', 'email', 'first_name', 'last_name', 'user_type', 'status'],
                    'tokens' => ['access_token', 'refresh_token'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'test@example.com',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'user_type' => 'end_user',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    });

    it('registers with optional fields', function () {
        $response = $this->postJson('/api/v1/public/auth/register', [
            'email' => 'agent@example.com',
            'password' => 'Password123!',
            'first_name' => 'Jane',
            'last_name' => 'Agent',
            'phone' => '+41 79 123 45 67',
            'user_type' => 'owner',
            'preferred_language' => 'fr',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'user_type' => 'owner',
                        'preferred_language' => 'fr',
                    ],
                ],
            ]);
    });

    it('fails with missing required fields', function () {
        $response = $this->postJson('/api/v1/public/auth/register', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validation Error'])
            ->assertJsonStructure(['errors']);
    });

    it('fails with invalid email', function () {
        $response = $this->postJson('/api/v1/public/auth/register', [
            'email' => 'not-an-email',
            'password' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(422);
    });

    it('fails with weak password', function () {
        $response = $this->postJson('/api/v1/public/auth/register', [
            'email' => 'test@example.com',
            'password' => 'weak',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(422);
    });

    it('fails with duplicate email', function () {
        createUser(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/public/auth/register', [
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(422);
    });
});

// ────────────────────────────────────────────────────────────────
// Login
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/login', function () {

    it('logs in with valid credentials', function () {
        createUser(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/public/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['_id', 'email', 'first_name', 'last_name', 'user_type'],
                    'tokens' => ['access_token', 'refresh_token'],
                ],
            ])
            ->assertJson(['success' => true]);
    });

    it('fails with wrong password', function () {
        createUser(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/public/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(422);
    });

    it('fails with non-existent email', function () {
        $response = $this->postJson('/api/v1/public/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422);
    });

    it('fails for suspended user', function () {
        createUser([
            'email' => 'suspended@example.com',
            'status' => AccountStatus::SUSPENDED,
        ]);

        $response = $this->postJson('/api/v1/public/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422);
    });

    it('fails with missing fields', function () {
        $response = $this->postJson('/api/v1/public/auth/login', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    });
});

// ────────────────────────────────────────────────────────────────
// Logout
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/logout', function () {

    it('logs out authenticated user', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/public/auth/logout');

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails for unauthenticated user', function () {
        $response = $this->postJson('/api/v1/public/auth/logout');

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Me (profile)
// ────────────────────────────────────────────────────────────────

describe('GET /api/v1/public/auth/me', function () {

    it('returns authenticated user profile', function () {
        $user = createUser([
            'email' => 'me@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->getJson('/api/v1/public/auth/me');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'me@example.com',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                ],
            ]);
    });

    it('fails for unauthenticated request', function () {
        $response = $this->getJson('/api/v1/public/auth/me');

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Update Profile
// ────────────────────────────────────────────────────────────────

describe('PATCH /api/v1/public/auth/me', function () {

    it('updates user profile', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->patchJson('/api/v1/public/auth/me', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                ],
            ]);
    });

    it('fails for unauthenticated request', function () {
        $response = $this->patchJson('/api/v1/public/auth/me', [
            'first_name' => 'Updated',
        ]);

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Change Password
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/change-password', function () {

    it('changes password with valid current password', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/public/auth/change-password', [
            'current_password' => 'Password123!',
            'new_password' => 'NewPassword456!',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Verify new password works
        expect(Hash::check('NewPassword456!', $user->fresh()->password))->toBeTrue();
    });

    it('fails with wrong current password', function () {
        $user = createUser();
        $headers = loginAs($user);

        $response = $this->withHeaders($headers)->postJson('/api/v1/public/auth/change-password', [
            'current_password' => 'WrongPassword1!',
            'new_password' => 'NewPassword456!',
        ]);

        $response->assertStatus(422);
    });

    it('fails for unauthenticated request', function () {
        $response = $this->postJson('/api/v1/public/auth/change-password', [
            'current_password' => 'Password123!',
            'new_password' => 'NewPassword456!',
        ]);

        $response->assertStatus(401);
    });
});

// ────────────────────────────────────────────────────────────────
// Forgot Password
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/forgot-password', function () {

    it('sends password reset email for existing user', function () {
        createUser(['email' => 'forgot@example.com']);

        $response = $this->postJson('/api/v1/public/auth/forgot-password', [
            'email' => 'forgot@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns success even for non-existent email (prevents enumeration)', function () {
        $response = $this->postJson('/api/v1/public/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails with invalid email format', function () {
        $response = $this->postJson('/api/v1/public/auth/forgot-password', [
            'email' => 'not-valid',
        ]);

        $response->assertStatus(422);
    });
});

// ────────────────────────────────────────────────────────────────
// Refresh Token
// ────────────────────────────────────────────────────────────────

describe('POST /api/v1/public/auth/refresh', function () {

    it('refreshes tokens with valid refresh token', function () {
        $user = createUser();

        // Login first to get refresh token
        $loginResponse = $this->postJson('/api/v1/public/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $refreshToken = $loginResponse->json('data.tokens.refresh_token');

        $response = $this->postJson('/api/v1/public/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['access_token', 'refresh_token'],
            ]);
    });

    it('fails with invalid refresh token', function () {
        $response = $this->postJson('/api/v1/public/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(422);
    });

    it('fails with missing refresh token', function () {
        $response = $this->postJson('/api/v1/public/auth/refresh', []);

        $response->assertStatus(422);
    });
});
