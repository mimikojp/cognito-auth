<?php

namespace Adcate\CognitoAuth\Http\Controllers;

use Adcate\CognitoAuth\UseCases\Authenticate;
use Adcate\CognitoAuth\UseCases\SignIn;
use Adcate\CognitoAuth\UseCases\SignOut;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthenticateControllerTest
 * @package Adcate\CognitoAuth\Http\Controllers
 * @runTestsInSeparateProcesses
 */
class AuthenticateControllerTest extends TestCase
{
    /**
     * @test
     * @testdox this::signInは実行時にUseCaseのSignInを呼び出す
     * @covers \Adcate\CognitoAuth\Http\Controllers\AuthenticateController::signIn
     */
    public function callSignInOfUseCase()
    {
        $controller = new AuthenticateController();

        $spyUseCase = Mockery::spy(SignIn::class);
        $spyUseCase->shouldReceive('invoke');

        $controller->signIn($spyUseCase);

        $spyUseCase->shouldHaveReceived('invoke');
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox this::callbackは実行時にUseCaseのAuthenticateを呼び出す
     * @covers \Adcate\CognitoAuth\Http\Controllers\AuthenticateController::callback
     */
    public function callAuthenticateOfUseCase()
    {
        $controller = new AuthenticateController();

        $spyUseCase = Mockery::spy(Authenticate::class);
        $spyUseCase->shouldReceive('invoke');

        $controller->callback(
            $mockRequest = Mockery::mock(Request::class),
            $spyUseCase
        );

        $spyUseCase->shouldHaveReceived('invoke');
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox this::signOutは実行時にUseCaseのSignOutを呼び出す
     * @covers \Adcate\CognitoAuth\Http\Controllers\AuthenticateController::signOut
     */
     public function callSignOutOfUseCase()
     {
         $controller = new AuthenticateController();

         $spyUseCase = Mockery::spy(SignOut::class);
         $spyUseCase->shouldReceive('invoke');

         $mockRequest = Mockery::mock(Request::class);
         $mockRequest->shouldReceive('bearerToken')->andReturn('token');

         $controller->signOut($mockRequest, $spyUseCase);

         $spyUseCase->shouldHaveReceived('invoke', ['token']);
         $this->assertTrue(true);
     }
}
