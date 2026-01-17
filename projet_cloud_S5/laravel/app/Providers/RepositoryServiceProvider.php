<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\UserRepositoryInterface;
use App\Repositories\FirebaseUserRepository;
use App\Repositories\PostgresUserRepository;
use App\Services\ConnectivityService;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            $connectivity = new ConnectivityService();
            
            // Si connecté à Internet, utiliser Firebase
            if ($connectivity->isOnline()) {
                return new FirebaseUserRepository();
            }
            
            // Sinon, utiliser PostgreSQL local
            return new PostgresUserRepository();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}