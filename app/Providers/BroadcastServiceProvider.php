<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes();

        require base_path('routes/channels.php');

        // Log all broadcast events
        Event::listen('Illuminate\\Broadcasting\\Events\\BroadcastOn', function ($event) {
            Log::info('Broadcast event: ' . $event->event, $event->data);
        });
    }
} 