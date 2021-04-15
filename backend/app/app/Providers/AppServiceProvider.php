<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prop\Services\Mailman\Mailman;
use Prop\Services\Mailman\MailmanImpl;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Mailman::class, function ($app) {
            return new MailmanImpl();
        });
    }

    public function boot()
    {
        //
    }
}
