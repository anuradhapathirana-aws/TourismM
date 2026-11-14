<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Permissions;
use App\Support\Validation\UserRules;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Development guards: unfillable attributes throw; N+1 lazy loading is logged.
        if (! $this->app->isProduction()) {
            Model::preventSilentlyDiscardingAttributes();
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(
                fn (Model $model, string $relation) => logger()->warning('N+1: lazy loaded '.$model::class."::{$relation}")
            );
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Super Admin passes every policy / permission check.
        Gate::before(fn (User $user): ?bool => $user->is_active && $user->hasRole(Permissions::SUPER_ADMIN) ? true : null);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(UserRules::passwordMinLength())->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(UserRules::passwordMinLength()));

        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
