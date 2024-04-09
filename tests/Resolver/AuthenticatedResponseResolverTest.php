<?php

namespace Adcate\CognitoAuth\Resolver;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthenticatedResponseResolverTest
 * @package Adcate\CognitoAuth\Resolver
 * @runTestsInSeparateProcesses
*/
class AuthenticatedResponseResolverTest extends TestCase
{
    /**
     * @test
     * @testdox リゾルバーは対象URLのURLフラグメントにトークン文字列をセットした状態でリダイレクトさせる
     * @covers \Adcate\CognitoAuth\Resolver\AuthenticatedResponseResolver::resolve
     */
    public function resolveAuthorizedAccessTokenInFragment()
    {
        $container = Mockery::spy('overload:' . Container::class);
        $container->shouldReceive('getInstance')->andReturnSelf();
        $container->shouldReceive('make')->with(ResponseFactory::class, [])->andReturn(
            $spyFactory = Mockery::spy(ResponseFactory::class)
        );
        $spyFactory->shouldReceive('redirectTo')->andReturn(
            Mockery::mock(RedirectResponse::class)
        );

        $resolver = new AuthenticatedResponseResolver();
        $resolver->resolve('http://example.com', 'token');

        $spyFactory->shouldHaveReceived('redirectTo', ['http://example.com#token']);
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
