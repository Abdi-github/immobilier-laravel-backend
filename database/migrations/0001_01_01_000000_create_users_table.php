<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cantons
        Schema::create('cantons', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->json('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cities
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canton_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->string('postal_code', 10);
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['canton_id', 'postal_code']);
            $table->index('postal_code');
            $table->index('is_active');
        });

        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20);
            $table->json('name');
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Amenities
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('group', 20);
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agencies
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 250)->unique();
            $table->json('description')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('contact_person', 200)->nullable();
            $table->string('address', 500);
            $table->foreignId('city_id')->constrained();
            $table->foreignId('canton_id')->constrained();
            $table->string('postal_code', 10)->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verification_date')->nullable();
            $table->integer('total_properties')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_verified']);
            $table->index(['canton_id', 'city_id']);
            $table->index(['canton_id', 'status']);
            $table->fullText(['name', 'address']);
        });

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 50)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('user_type', 20)->default('end_user');
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preferred_language', 2)->default('en');
            $table->json('notification_preferences')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token', 255)->nullable();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->string('password_reset_token', 255)->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_type', 'status']);
            $table->index(['agency_id', 'user_type']);
            $table->fullText(['first_name', 'last_name', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('agencies');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('cantons');
    }
};
