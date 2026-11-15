<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tours: TourCategory 1─* Tour 1─* TourDay *─* Destination (ordered), Tour 1─* TourPrice (group-size price table).
 * Duration is not stored: it is the number of itinerary days, so the two can never disagree.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_category_id')->constrained()->restrictOnDelete();
            $table->string('title', 150)->unique();
            $table->string('slug', 170)->unique();
            $table->string('summary', 300);
            $table->text('description')->nullable();          // rich HTML (purified)
            $table->json('highlights')->nullable();           // list<string>
            $table->json('inclusions')->nullable();           // list<string>
            $table->json('exclusions')->nullable();           // list<string>
            $table->string('pace', 20)->default('moderate');  // TourPace
            $table->unsignedTinyInteger('group_min')->default(1);
            $table->unsignedTinyInteger('group_max')->nullable();
            $table->string('start_point', 120)->nullable();   // e.g. Colombo Airport (BIA)
            $table->string('end_point', 120)->nullable();
            $table->json('best_months')->nullable();          // list<int 1-12>
            $table->string('status', 20)->default('draft');   // TourStatus
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
        });

        Schema::create('tour_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title', 150);
            $table->text('description')->nullable();          // plain text
            $table->foreignId('overnight_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('hotel', 150)->nullable();         // "Jetwing Lake or similar, 4★"
            $table->json('meals')->nullable();                // ["breakfast","lunch","dinner"]
            $table->json('activities')->nullable();           // list<string>
            $table->unsignedSmallInteger('distance_km')->nullable();
            $table->decimal('drive_hours', 3, 1)->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'day_number']);
        });

        Schema::create('destination_tour_day', function (Blueprint $table): void {
            $table->foreignId('tour_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0); // visit order within the day
            $table->primary(['tour_day_id', 'destination_id']);
            $table->index('destination_id');                        // "tours that visit this point"
        });

        Schema::create('tour_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pax_from');
            $table->unsignedTinyInteger('pax_to')->nullable();       // null = "and more"
            $table->string('vehicle', 80)->nullable();
            $table->decimal('price_per_person', 10, 2);
            $table->timestamps();

            $table->index(['tour_id', 'pax_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_prices');
        Schema::dropIfExists('destination_tour_day');
        Schema::dropIfExists('tour_days');
        Schema::dropIfExists('tours');
    }
};
