<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 255)->nullable();
            $table->integer('version')->nullable();
            $table->string('signature', 255)->nullable();
            $table->string('url', 1000);
            $table->string('secure_url', 1000)->nullable();
            $table->string('thumbnail_url', 1000)->nullable();
            $table->string('thumbnail_secure_url', 1000)->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('format', 20)->nullable();
            $table->integer('bytes')->nullable();
            $table->string('resource_type', 20)->default('image');
            $table->string('alt_text', 500)->nullable();
            $table->string('caption', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('source', 20)->default('cloudinary');
            $table->string('original_filename', 500)->nullable();
            $table->string('external_url', 1000)->nullable();
            $table->string('original_url', 1000)->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'sort_order']);
            $table->index(['property_id', 'is_primary']);
            $table->index('public_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
