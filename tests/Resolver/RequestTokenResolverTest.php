<?php

namespace Adcate\CognitoAuth\Resolver;

use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;

class RequestTokenResolverTest extends TestCase
{
    /**
     * @test
     * @testdox Requestインスタンスからbearerトークンを取得し返却する
     * @covers \Adcate\CognitoAuth\Resolver\RequestTokenResolver::resolve()
     */
    public function returnBearerTokenFromRequest()
    {
        $resolver = new RequestTokenResolver();
        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->expects('bearerToken')->andReturn('return-bearer-token');
        $actual = $resolver->resolve($mockRequest);
        $this->assertEquals('return-bearer-token', $actual);
    }
}
