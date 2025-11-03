<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 100)->unique();
            $table->string('external_url', 1000)->nullable();
            $table->string('source_language', 2)->default('en');
            $table->foreignId('category_id')->constrained();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_type', 10);
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('CHF');
            $table->decimal('additional_costs', 10, 2)->nullable();
            $table->decimal('rooms', 4, 1)->nullable();
            $table->decimal('surface', 10, 2)->nullable();
            $table->string('address', 500);
            $table->foreignId('city_id')->constrained();
            $table->foreignId('canton_id')->constrained();
            $table->string('postal_code', 10)->nullable();
            $table->json('proximity')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['canton_id', 'city_id', 'status']);
            $table->index(['transaction_type', 'category_id', 'status']);
            $table->index(['price', 'status']);
            $table->index(['rooms', 'surface']);
            $table->index(['agency_id', 'status']);
            $table->index(['published_at', 'id']);
        });

        // Pivot: property_amenity
        Schema::create('property_amenity', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();

            $table->unique(['property_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_amenity');
        Schema::dropIfExists('properties');
    }
};
