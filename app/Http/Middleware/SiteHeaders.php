<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/** Security headers for the public site. (The admin panel needs inline scripts, so it is not covered.) */
class SiteHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'no-cache');
        if (! Vite::isRunningHot()) {
            // Google Analytics hosts are allowed only once the owner has added an ID in Settings.
            $ga = Seo::analyticsId(Setting::get('ga_id')) !== ''
                ? ' https://www.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com' : '';
            $response->headers->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data: blob:$ga; "
                ."style-src 'self' 'unsafe-inline'; font-src 'self'; script-src 'self'$ga; connect-src 'self'$ga; "
                ."manifest-src 'self'; worker-src 'self'; frame-src https://www.google.com; "
                ."frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        }

        return $response;
    }
}
