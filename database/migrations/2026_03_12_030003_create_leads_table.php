<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_first_name', 100);
            $table->string('contact_last_name', 100);
            $table->string('contact_email', 255);
            $table->string('contact_phone', 50)->nullable();
            $table->string('preferred_contact_method', 10)->default('email');
            $table->string('preferred_language', 2)->default('en');
            $table->string('inquiry_type', 30);
            $table->text('message');
            $table->string('status', 20)->default('NEW');
            $table->string('priority', 10)->default('medium');
            $table->string('source', 20)->default('website');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('viewing_scheduled_at')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status', 'created_at']);
            $table->index(['assigned_to', 'status', 'created_at']);
            $table->index(['property_id', 'created_at']);
            $table->index(['contact_email', 'created_at']);
            $table->index(['status', 'priority', 'created_at']);
            $table->index(['follow_up_date', 'status']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_internal')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
