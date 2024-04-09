<?php

namespace Adcate\CognitoAuth\Resolver;

use Illuminate\Http\Response;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

/**
 * Class RenewalTokenResponseResolverTest
 * @package Adcate\CognitoAuth\Resolver
 * @runTestsInSeparateProcesses
 */
class RenewalTokenResponseResolverTest extends TestCase
{
    /**
     * @test
     * @testdox リゾルバーはレスポンスオブジェクトのヘッダーに渡されたトークンをセットする
     */
    public function resolverSetTheToken()
    {
        $resolver = new RenewalTokenResponseResolver();
        $actual = $resolver->resolve(new Response(), new AccessToken(['access_token' => 'token']));
        $this->assertEquals('token', $actual->headers->get('X-Renew-Authorization'));
    }
}
