<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CorsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add global CORS headers to all responses
        app()->afterResolving('router', function ($router) {
            $router->pushMiddlewareToGroup('api', function ($request, $next) {
                if ($request->getMethod() === "OPTIONS") {
                    return response('', 200)
                        ->header('Access-Control-Allow-Origin', 'https://zultodolist.netlify.app')
                        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
                }

                $response = $next($request);
                $response->headers->set('Access-Control-Allow-Origin', 'https://zultodolist.netlify.app');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');

                return $response;
            });
        });
    }
}
