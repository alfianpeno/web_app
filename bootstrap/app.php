<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\FilterRequest;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        // commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware
        $middleware->append([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // RouteMiddleware / Alias
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'XSS' => \App\Http\Middleware\XSS::class,
            'revalidate' => \App\Http\Middleware\RevalidateBackHistory::class,
        ]);

        // middlewareGroups / Group Middleware
        // Append middleware to the 'web' group
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \App\Http\Middleware\FilterRequest::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Append middleware to the 'api' group
        $middleware->appendToGroup('api', [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Exclude specific routes from CSRF protection
        $middleware->validateCsrfTokens(
            except: [
                // 'plan/paytm/*',
                '/customer/invoice/paytm*/',
                '/customer/invoice/flaterwave*/',
                '/invoice/razorpay*/',
                '/customer/retainer/paytm*/',
                'iyzipay/callback/*',
                'invoice/iyzipay/callback/*',
                'retainer/iyzipay/callback/*',
                'paytab-success/*',
                'retainer-paytab-success/*',
                '/aamarpay*'
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
