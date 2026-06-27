<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeprecatedApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', 'Sat, 01 Jan 2027 00:00:00 GMT');
        $response->headers->set('Link', '<https://api.example.com/v2>; rel="successor-version"');
        $response->headers->set('Warning', '299 - "API v1 is deprecated. Please migrate to v2."');

        return $response;
    }
}
