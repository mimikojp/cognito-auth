<?php


namespace Adcate\CognitoAuth\Repository\Cache;


use Adcate\CognitoAuth\Repository\OAuthStateRepository;
use Illuminate\Support\Facades\Cache;

class CacheOAuthStateRepository implements OAuthStateRepository
{

    public function exists(string $state): bool
    {
        $exists = Cache::has('auth.beforeAuthorize.state.' . $state);
        if ($exists) {
            Cache::forget('auth.beforeAuthorize.state.' . $state);
        }
        return $exists;
    }

    public function put(string $state): void
    {
        Cache::put('auth.beforeAuthorize.state.' . $state, 1, now()->addMinutes(5));
    }
}
