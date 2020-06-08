<?php


namespace App\Http\Middleware;

use Closure;
use \Firebase\JWT\JWT;


class AuthF6
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jwt = substr($request->header('authorization'), 7);
        if (!$jwt) {
            return response()->json(["error" => 'access_denined', 'error_description' => 'Không được phép truy cập']);
        }
        // Pre-Middleware Action
        $public_key = "-----BEGIN PUBLIC KEY-----\n" . env('KEYCLOAK_JWT_PUBLIC_KEY') . "\n-----END PUBLIC KEY-----";

        try {
            $profile = JWT::decode($jwt, $public_key, array('RS256'));
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['error' => 'access_token_invalid', 'error_description' => $e->getMessage()]);
        }
        $request->merge(['employee_id' => $profile->preferred_username, 'profile' => (array)$profile]);
        $response = $next($request);
        // Post-Middleware Action

        return $response;
    }
}
