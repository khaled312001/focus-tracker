<?php

namespace App\Providers;

use App\Models\Meeting;
use App\Policies\MeetingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Meeting::class => MeetingPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
} 