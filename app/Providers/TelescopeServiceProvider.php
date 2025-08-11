<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->gate();
        $this->hideSensitiveRequestDetails();

        Telescope::auth(function ($request) {
            $user = $request->user();
            return $user && $user->is_admin;
        });

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
   protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return $user && $user->is_admin;
        });
    }

     /**
     * Override this method to register routes with 'auth' middleware.
     */
    protected function registerRoutes(): void
    {
        $this->app['router']->group([
            'prefix' => 'telescope',
            'middleware' => ['web', 'auth'],  // add auth middleware here
            'namespace' => 'Laravel\Telescope\Http\Controllers',
        ], function () {
            require base_path('vendor/laravel/telescope/routes/telescope.php');
        });
    }
}
