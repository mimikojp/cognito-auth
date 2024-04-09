<?php


namespace Adcate\CognitoAuth\Repository\Cache;


use Adcate\CognitoAuth\Repository\RefreshTokenRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CacheRefreshTokenRepository implements RefreshTokenRepository
{
    public function get(string $accessToken): ?string
    {
         return Cache::pull('auth.refresh_token.' . $accessToken);
    }

    public function put(string $accessToken, string $refreshToken): void
    {
        Cache::put('auth.refresh_token.' . $accessToken, $refreshToken, Carbon::now()->addDays(3));
    }

    public function delete(string $accessToken): void
    {
        Cache::forget('auth.refresh_token.' . $accessToken);
    }
}
