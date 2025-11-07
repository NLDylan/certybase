<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\Design;
use App\Models\DesignTemplate;
use App\Models\Organization;
use App\Observers\CertificateObserver;
use App\Policies\CampaignPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\DesignPolicy;
use App\Policies\DesignTemplatePolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Design::class => DesignPolicy::class,
        Campaign::class => CampaignPolicy::class,
        Certificate::class => CertificatePolicy::class,
        DesignTemplate::class => DesignTemplatePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Organization as the Cashier billable model
        Cashier::useCustomerModel(Organization::class);

        Certificate::observe(CertificateObserver::class);

        // Define media remote download rate limiter
        RateLimiter::for('media-remote', function ($request) {
            $userId = (string) optional($request->user())->getAuthIdentifier();
            $by = $userId !== '' ? 'user:'.$userId : 'ip:'.$request->ip();

            return [Limit::perMinute(10)->by($by)];
        });
    }
}
