<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Tenant-Key');

        if (! $apiKey) {
            return response()->json(['error' => 'Missing tenant API key'], 401);
        }

        // store attributes not the model instance — avoids Redis deserialization issues
        $tenantData = Cache::remember(
            "tenant:key:{$apiKey}",
            now()->addMinutes(5),
            fn () => Tenant::where('api_key', $apiKey)
                ->where('is_active', true)
                ->first()
                ?->toArray()
        );

        if (! $tenantData) {
            return response()->json(['error' => 'Invalid or inactive tenant'], 401);
        }

        $tenant = (new Tenant())->forceFill($tenantData);

        $request->merge(['_tenant' => $tenantData]);
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
