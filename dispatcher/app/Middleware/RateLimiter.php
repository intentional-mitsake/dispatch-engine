<?php

namespace App\Middleware;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class RateLimiter
{
    private $luascript;
    public function __construct()
    {
        $this->luascript = file_get_contents(__DIR__ . '/script.lua');;
    }
    public function handle($request, $next)
    {
        try{
            // if authenticated, use the user id as the rate limit key, if not authenticated, use the header(X-API-Key) else use the ip
            // can set header but will use ip for postman tests
            $rateLimitKey = $request->user()?->id  ?? $request->header('X-API-Key')?? $request->ip();
            $result = Redis::eval(// runs the lua script in redis, passing the keys and arguments
                $this->luascript,
                1, // number of keys is always 2nd arg for eval
                // redis keys come first, then the arguments
                "IP" . $rateLimitKey, //keys-ARGV[1]
                10, // capacity-ARGV[1]
                2, // refill rate-ARGV[2]
                time(), // current time in seconds-ARGV[3]
                1 // cost of the request-ARGV[4]
            );
            // result will get an array of 2 elements, first is the remaining tokens, second is the time to wait in seconds
            // so in go i used []interface{} to store any value that comes
            if($result[0] == 0){
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
            Log::info("Rate limit not exceeded: $result[1] tokens left");
            return $next($request);
        } catch (\Exception $e) {
            Log::error("$e: IP" . $rateLimitKey);
            return response()->json("Redis error:" . $e->getMessage());
            // will route to next in case of redis error or any other 
            // not for rate limit
            return $next($request);
        }
    }
}