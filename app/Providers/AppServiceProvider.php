<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Database\Eloquent\Model;
use App\Support\QueryStats;
use App\Support\SlowQueryLogger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Answer;
use App\Models\Question;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QueryStats::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-tags', function (User $user) {
            return $user->hasRole(Role::Admin , Role::Editor);
        });

        RateLimiter::for('api', fn (Request $request) =>
            Limit::perMinute(60)->by($request->user('sanctum')?->id ?: $request->ip())
        );

        RateLimiter::for('api-login', fn(Request $request) =>
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')). '|'.$request->ip())
        );

        RateLimiter::for('summaries', fn (Request $request) =>
            Limit::perMinute(5)->by($request->user('sanctum')?->id ?: $request->ip())
        );

        Model::preventLazyLoading(! $this->app->isProduction());
        
        DB::listen(function (QueryExecuted $query) {
            app(QueryStats::class)->record($query->time);
            SlowQueryLogger::handle($query);
        });

        // store short names, not class names, in votable_type; unmapped types (your comments) are untouched
        Relation::morphMap([
            'question' => Question::class,
            'answer'   => Answer::class,
        ]);
    }
}
