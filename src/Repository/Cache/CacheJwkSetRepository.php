<?php


namespace Adcate\CognitoAuth\Repository\Cache;


use Adcate\CognitoAuth\Repository\JwkSetRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CacheJwkSetRepository implements JwkSetRepository
{
    public function get(): ?string
    {
        return Cache::get('auth.jwkSet');
    }

    public function put(string $jwkSet): void
    {
        Cache::put('auth.jwkSet', $jwkSet, Carbon::now()->addHours(12));
    }
}
