<?php

namespace Adcate\CognitoAuth\Provider;

use Adcate\CognitoAuth\Auth\CognitoAuthProvider;
use Adcate\CognitoAuth\Repository\Cache\CacheJwkSetRepository;
use Adcate\CognitoAuth\Repository\Cache\CacheOAuthStateRepository;
use Adcate\CognitoAuth\Repository\Cache\CacheRefreshTokenRepository;
use Adcate\CognitoAuth\Repository\JwkSetRepository;
use Adcate\CognitoAuth\Repository\OAuthStateRepository;
use Adcate\CognitoAuth\Repository\RefreshTokenRepository;
use Adcate\CognitoAuth\Resolver\AuthenticatedResponseResolver;
use Adcate\CognitoAuth\Resolver\RenewalTokenResponseResolver;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class ServiceProvider extends BaseServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../../config/cognito.php';
    const CONFIG_OUTPUT_PATH = 'cognito.php';

    public function register()
    {
        $this->registerOauthProvider();
        $this->registerJWSSerializerManager();
        $this->registerJWSVerifier();

        $this->registerRepositories();
        $this->registerResolvers();
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/auth.php');

        $this->publishes([
            $this::CONFIG_PATH => config_path($this::CONFIG_OUTPUT_PATH)
        ]);
    }

    private function registerOauthProvider()
    {
        $this->app->bind(CognitoAuthProvider::class, function () {
            $oauth = config('cognito.provider');
            return new CognitoAuthProvider($oauth);
        });
    }

    private function registerJWSSerializerManager()
    {
        $manager = new JWSSerializerManager([new CompactSerializer()]);
        $this->app->instance(JWSSerializerManager::class, $manager);
    }

    private function registerJWSVerifier()
    {
        $alg = new AlgorithmManager([new RS256()]);
        $this->app->instance(JWSVerifier::class, new JWSVerifier($alg));
    }

    private function registerResolvers()
    {
        $this->app->instance(AuthenticatedResponseResolver::class, new AuthenticatedResponseResolver());
        $this->app->instance(RenewalTokenResponseResolver::class, new RenewalTokenResponseResolver());
    }

    private function registerRepositories()
    {
        $this->app->instance(JwkSetRepository::class, new CacheJwkSetRepository());
        $this->app->instance(RefreshTokenRepository::class, new CacheRefreshTokenRepository());
        $this->app->instance(OAuthStateRepository::class, new CacheOAuthStateRepository());
    }
}
