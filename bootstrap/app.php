<?php

use App\Http\Middleware\ApiRequestLogger;
use App\Http\Middleware\AuthorizeArticleAction;
use App\Http\Middleware\AuthorizeUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // every request cross here :  rate Limiting and RequestLogger
        $middleware->appendToGroup(
            'api',
            [
                'api.logger',
                'throttle:api',
            ]
        );

        $middleware->alias([
            'authorize' => AuthorizeUser::class,
            'authorize.article' => AuthorizeArticleAction::class,
            'api.logger' => ApiRequestLogger::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
