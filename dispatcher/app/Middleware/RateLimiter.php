<?php

namespace App\Middleware;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

$luascript = file_get_contents(__DIR__ . '/script.lua');

class RateLimiter
{
    public function handle($request, $next)
    {
        // Redis::eval(lua_script, num_keys, key1, key2, ..., arg1, arg2, ...)-->runs lua script on redis
        $result = Redis::eval($luascript, 1, 'rate_limiter', 10, 1); // key, max_tokens, refill_rate
        return $next($request);
    }
}