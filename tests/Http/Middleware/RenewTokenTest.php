<?php

namespace Adcate\CognitoAuth\Http\Middleware;


use Adcate\CognitoAuth\Auth\TokenHelper;
use Adcate\CognitoAuth\Resolver\RenewalTokenResponseResolver;
use Adcate\CognitoAuth\Resolver\RequestTokenResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class RenewTokenTest
 * @package Adcate\CognitoAuth\Http\Middleware
 * @runTestsInSeparateProcesses
 */
class RenewTokenTest extends TestCase
{
    protected function setUp(): void
    {
        Log::spy();
    }

    /**
     * @test
     * @testdox 署名を検証した結果、クレームが取得できなかった場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\RenewToken::handle
     */
    public function throwExceptionWhenCouldNotGetClaim()
    {
        $this->expectException(AuthenticationException::class);

        $middleware = new RenewToken(
            $mockHelper = Mockery::mock(TokenHelper::class),
            $mockTokenResolver = Mockery::mock(RequestTokenResolver::class),
            Mockery::mock(RenewalTokenResponseResolver::class)
        );

        $mockTokenResolver->expects('resolve')->andReturn('');
        $mockHelper->shouldReceive('validate')->andReturnNull();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $middleware->handle($request, function () {});
    }

    /**
     * @test
     * @testdox ユーザーがAPI利用認可を受けていない場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\RenewToken::handle
     */
    public function throwExceptionWhenNotAuthorized()
    {
        $this->expectException(AuthenticationException::class);

        $middleware = new RenewToken(
            $mockHelper = Mockery::mock(TokenHelper::class),
            $mockTokenResolver = Mockery::mock(RequestTokenResolver::class),
            Mockery::mock(RenewalTokenResponseResolver::class)
        );

        $mockTokenResolver->expects('resolve')->andReturn('');
        $mockHelper->shouldReceive('validate')->andReturnNull();
        $mockHelper->shouldReceive('isAuthorized')->andReturnFalse();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $middleware->handle($request, function () {});
    }

    /**
     * @test
     * @testdox トークンの有効期限が切れている場合、AccessTokenを再取得し、新しいトークンがresponseヘッダーにセットされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\RenewToken::handle
     */
    public function returnRenewTokenWhenTokenExpired()
    {
        $middleware = new RenewToken(
            $mockHelper = Mockery::mock(TokenHelper::class),
            $mockTokenResolver = Mockery::mock(RequestTokenResolver::class),
            $spyResolver = Mockery::spy(RenewalTokenResponseResolver::class)
        );

        $mockTokenResolver->expects('resolve')->andReturn('');
        $mockHelper->shouldReceive('validate')->andReturn([]);
        $mockHelper->shouldReceive('isAuthorized')->andReturnTrue();
        $mockHelper->shouldReceive('isExpired')->andReturnTrue();
        $mockHelper->shouldReceive('renew')->andReturn(new AccessToken(['access_token' => 'renewed-token']));

        $spyResolver->shouldReceive('resolve');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $response = new Response();
        $closure = function () use ($response) {
            return $response;
        };
        $middleware->handle($request, $closure);

        $spyResolver->shouldHaveReceived('resolve', [$response, 'renewed-token']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox トークンが有効期限内の場合、更新用ヘッダーは付与されない
     * @covers \Adcate\CognitoAuth\Http\Middleware\RenewToken::handle
     */
    public function NotUpdateTokenWhenTokenAvailable()
    {
        $middleware = new RenewToken(
            $mockHelper = Mockery::mock(TokenHelper::class),
            $mockTokenResolver = Mockery::mock(RequestTokenResolver::class),
            $spyResolver = Mockery::spy(RenewalTokenResponseResolver::class)
        );

        $mockTokenResolver->expects('resolve')->andReturn('');
        $mockHelper->shouldReceive('validate')->andReturn(['sub' => 'test']);
        $mockHelper->shouldReceive('isAuthorized')->andReturnTrue();
        $mockHelper->shouldReceive('isExpired')->andReturnFalse();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $response = new Response();
        $closure = function () use ($response) {
            return $response;
        };
        $actual = $middleware->handle($request, $closure);

        $spyResolver->shouldNotHaveReceived('resolve');
        $this->assertEquals($response, $actual);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
