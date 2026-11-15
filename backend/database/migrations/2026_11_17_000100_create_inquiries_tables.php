<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inquiries module.
 *   guests 1─* inquiries *─* destinations (destination_inquiry, ordered)
 *                        *─* activities   (activity_inquiry, ordered)
 *                        1─* inquiry_notes (staff timeline: notes + status changes)
 *                        *─1 users (assigned staff) · *─1 tours (package inquiries, later)
 * Guests are kept separately so one person's details are stored once and reused by every inquiry
 * (and later by bookings, reviews, newsletters).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->unique();      // lower-cased; the key used to recognise returning guests
            $table->string('phone', 30)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('preferred_contact', 20)->nullable();
            $table->text('notes')->nullable();            // staff-only notes about the person
            $table->softDeletes();
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->nullable()->unique(); // LG-2611-00042, set right after insert
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('custom_tour');
            $table->string('source', 20)->default('website');
            $table->string('status', 20)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete(); // package inquiries

            // Trip
            $table->date('arrival_date');
            $table->time('arrival_time')->nullable();
            $table->date('departure_date');
            $table->boolean('flexible_dates')->default(false);
            $table->string('arrival_airport', 20)->nullable();
            $table->string('occasion', 20)->nullable();
            $table->string('pace', 20)->nullable();

            // Travellers
            $table->unsignedTinyInteger('men')->default(0);
            $table->unsignedTinyInteger('women')->default(0);
            $table->unsignedTinyInteger('children')->default(0);
            $table->json('child_ages')->nullable();

            // Stay, transport, budget
            $table->string('accommodation', 20)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->string('meal_plan', 20)->nullable();
            $table->string('vehicle', 20)->nullable();
            $table->string('guide_language', 40)->nullable();
            $table->string('budget', 20)->nullable();
            $table->string('dietary', 300)->nullable();
            $table->string('accessibility', 300)->nullable();

            // Guest's own words + marketing
            $table->text('notes')->nullable();
            $table->string('referral', 60)->nullable();

            // Sales
            $table->decimal('quoted_amount', 10, 2)->nullable();
            $table->timestamp('closed_at')->nullable();   // set when status becomes booked/lost

            // Abuse investigation only (never shown publicly)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('arrival_date');
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('destination_inquiry', function (Blueprint $table): void {
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0); // order the guest picked them
            $table->primary(['inquiry_id', 'destination_id']);
            $table->index('destination_id');
        });

        Schema::create('activity_inquiry', function (Blueprint $table): void {
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->primary(['inquiry_id', 'activity_id']);
            $table->index('activity_id');
        });

        Schema::create('inquiry_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status_from', 20)->nullable();
            $table->string('status_to', 20)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
            $table->index(['inquiry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_notes');
        Schema::dropIfExists('activity_inquiry');
        Schema::dropIfExists('destination_inquiry');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('guests');
    }
};
