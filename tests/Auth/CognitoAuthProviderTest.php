<?php

namespace Adcate\CognitoAuth\Auth;

use Illuminate\Support\Facades\Config;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CognitoAuthProviderTest extends TestCase
{
    protected function setUp(): void
    {
        Config::shouldReceive('get')->with('cognito.provider')->andReturn([
            'redirectUri' => 'redirect-uri',
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'userPoolId' => 'user-pool-id',
            'urlAuthorize' => 'url-authorize',
            'urlAccessToken' => 'url-access-token',
            'urlResourceOwnerDetails' => 'url-resource-owner',
            'urlLogout' => 'url-logout',
        ]);
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigから認証エンドポイントのURLを取得する
     */
    public function setAuthorizationUrlWhenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $this->assertEquals('url-authorize', $provider->getBaseAuthorizationUrl());
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigからリソースオーナーエンドポイントのURLを取得する
     */
    public function setResourceOwnerUrlWhenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $this->assertEquals('url-resource-owner', $provider->getResourceOwnerDetailsUrl(\Mockery::mock(AccessToken::class)));
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigからトークンエンドポイントのURLを取得する
     */
    public function setAccessTokenUrlWhenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $this->assertEquals('url-access-token', $provider->getBaseAccessTokenUrl([]));
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigからクライアントIDを取得する
     */
    public function setClientIdWhenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $reflection = new ReflectionClass($provider);
        $reflectProp = $reflection->getProperty('clientId');
        $reflectProp->setAccessible(true);
        $this->assertEquals('client-id', $reflectProp->getValue($provider));
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigからクライアントシークレットを取得する
     */
    public function setClientSecretWhenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $reflection = new ReflectionClass($provider);
        $reflectProp = $reflection->getProperty('clientSecret');
        $reflectProp->setAccessible(true);
        $this->assertEquals('client-secret', $reflectProp->getValue($provider));
    }

    /**
     * @test
     * @testdox クラス初期化時にConfigからリダイレクトURIを取得する
     */
    public function setRedirectUriWHenConstruct()
    {
        $provider = new CognitoAuthProvider();
        $reflection = new ReflectionClass($provider);
        $reflectProp = $reflection->getProperty('redirectUri');
        $reflectProp->setAccessible(true);
        $this->assertEquals('redirect-uri', $reflectProp->getValue($provider));
    }
}

