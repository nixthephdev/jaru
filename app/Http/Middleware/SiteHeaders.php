<?php

namespace App\Http\Middleware;

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
            $response->headers->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data: blob:; "
                ."style-src 'self' 'unsafe-inline'; font-src 'self'; script-src 'self'; connect-src 'self'; "
                ."manifest-src 'self'; worker-src 'self'; frame-src https://www.google.com; "
                ."frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        }

        return $response;
    }
}
