<?php

namespace Adcate\CognitoAuth\Http\Middleware;

use Adcate\CognitoAuth\Auth\TokenHelper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class VerifyTokenTest
 * @package Adcate\CognitoAuth\Http\Middleware
 * @runTestsInSeparateProcesses
 */
class VerifyTokenTest extends TestCase
{
    protected function setUp(): void
    {
        Log::spy();
    }

    /**
     * @test
     * @testdox 署名を検証した結果、クレームが取得できなかった場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\VerifyToken::handle
     */
    public function throwExceptionWhenCouldNotGetClaim()
    {
        $this->expectException(AuthenticationException::class);

        $middleware = new VerifyToken(
            $mockHelper = Mockery::mock(TokenHelper::class)
        );

        $mockHelper->shouldReceive('validate')->andReturnNull();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $closure = function ($arg) use ($request) {
            $this->assertEquals($request, $arg);
            return 'method_called';
        };

        $middleware->handle($request, $closure);
    }

    /**
     * @test
     * @testdox ユーザーがAPI利用認可を受けていない場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\VerifyToken::handle
     */
    public function throwExceptionWhenNotAuthorized()
    {
        $this->expectException(AuthenticationException::class);

        $middleware = new VerifyToken(
            $mockHelper = Mockery::mock(TokenHelper::class)
        );

        $mockHelper->shouldReceive('validate')->andReturnNull();
        $mockHelper->shouldReceive('isAuthorized')->andReturnFalse();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $middleware->handle($request, function () {});
    }

    /**
     * @test
     * @testdox トークンの有効期限が切れている場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Http\Middleware\VerifyToken::handle
     */
    public function throwExceptionWhenTokenExpired()
    {
        $this->expectException(AuthenticationException::class);

        $middleware = new VerifyToken(
            $mockHelper = Mockery::mock(TokenHelper::class)
        );

        $mockHelper->shouldReceive('validate')->with('token')->andReturn([]);
        $mockHelper->shouldReceive('isAuthorized')->andReturnTrue();
        $mockHelper->shouldReceive('isExpired')->andReturnTrue();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $closure = function ($arg) use ($request) {
            $this->assertEquals($request, $arg);
            return 'method_called';
        };

        $middleware->handle($request, $closure);
    }

    /**
     * @test
     * @testdox トークンの署名が確認でき、且つ有効期限内の場合、Closureに処理が引き継がれる
     * @covers \Adcate\CognitoAuth\Http\Middleware\VerifyToken::handle
     */
    public function callNextHandleWhenTokenIsValid()
    {
        $middleware = new VerifyToken(
            $mockHelper = Mockery::mock(TokenHelper::class)
        );

        $mockHelper->shouldReceive('validate')->with('token')->andReturn([]);
        $mockHelper->shouldReceive('isAuthorized')->andReturnTrue();
        $mockHelper->shouldReceive('isExpired')->andReturnFalse();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('bearerToken')->andReturn('token');
        $actual = $middleware->handle($request, function ($arg) use ($request) {
            $this->assertEquals($request, $arg);
            return 'method_called';
        });

        $this->assertEquals('method_called', $actual);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
