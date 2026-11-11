<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class DeploymentProxies extends TrustProxies
{
    public function handle(Request $request, Closure $next)
    {
        $this->proxies = config('deployment.trusted_proxies', []);

        return parent::handle($request, $next);
    }
}
