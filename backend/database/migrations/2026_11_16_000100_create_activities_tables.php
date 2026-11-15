<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Activities library (Jeep safari, White-water rafting …) picked per itinerary day.
 * Replaces the free-text tour_days.activities JSON list: existing entries are moved into the library.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('activity_tour_day', function (Blueprint $table): void {
            $table->foreignId('tour_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->primary(['tour_day_id', 'activity_id']);
            $table->index('activity_id');
        });

        $this->moveFreeTextActivities();

        Schema::table('tour_days', fn (Blueprint $table) => $table->dropColumn('activities'));
    }

    public function down(): void
    {
        Schema::table('tour_days', fn (Blueprint $table) => $table->json('activities')->nullable()->after('meals'));

        DB::table('activity_tour_day')
            ->join('activities', 'activities.id', '=', 'activity_tour_day.activity_id')
            ->orderBy('activity_tour_day.sort_order')
            ->get(['tour_day_id', 'name'])
            ->groupBy('tour_day_id')
            ->each(fn ($rows, $dayId) => DB::table('tour_days')->where('id', $dayId)
                ->update(['activities' => json_encode($rows->pluck('name')->all())]));

        Schema::dropIfExists('activity_tour_day');
        Schema::dropIfExists('activities');
    }

    private function moveFreeTextActivities(): void
    {
        $now = now();
        $ids = [];

        DB::table('tour_days')->whereNotNull('activities')->orderBy('id')->get(['id', 'activities'])
            ->each(function (object $day) use (&$ids, $now): void {
                $names = collect(json_decode((string) $day->activities, true) ?: [])
                    ->map(fn ($n) => trim(strip_tags((string) $n)))->filter()->unique(fn ($n) => mb_strtolower($n))->values();

                $pivot = [];
                foreach ($names as $i => $name) {
                    $key = mb_strtolower($name);
                    if (! isset($ids[$key])) {
                        $existing = DB::table('activities')->whereRaw('lower(name) = ?', [$key])->value('id');
                        $ids[$key] = $existing ?? DB::table('activities')->insertGetId([
                            'name' => mb_substr($name, 0, 100),
                            'slug' => $this->uniqueSlug($name),
                            'is_active' => true,
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                    $pivot[$ids[$key]] = ['tour_day_id' => $day->id, 'activity_id' => $ids[$key], 'sort_order' => $i + 1];
                }
                if ($pivot) {
                    DB::table('activity_tour_day')->insert(array_values($pivot));
                }
            });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'activity', 110, '');
        $slug = $base;
        for ($i = 2; DB::table('activities')->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
};
