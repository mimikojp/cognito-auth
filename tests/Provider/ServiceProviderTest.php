<?php

namespace Adcate\CognitoAuth\Provider;

use Adcate\CognitoAuth\Http\Controllers\AuthenticateController;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use League\Flysystem\Config;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class ServiceProviderTest
 * @package Adcate\CognitoAuth\Provider
 * @runTestsInSeparateProcesses
 */
class ServiceProviderTest extends TestCase
{
    /**
     * @test
     * @testdox 設定にrouteが存在する場合、起動時に認証系エンドポイントをルート設定に追加する
     */
    public function setRoutingWhenLoadServiceProvider()
    {
        $config = [
            'cognito.route' => [
                'test' => 'test',
            ],
        ];

        $mockContainer = Mockery::mock('overload:' . Container::class);
        $mockContainer->shouldReceive('getInstance')->andReturnSelf();
        $mockContainer->shouldReceive('configPath')->andReturn('/config/path');
        $mockContainer->shouldReceive('make')->with('path.config')->andReturn('/path/config');
        $mockContainer->shouldReceive('make')->with('config', [])->andReturn($config = new Config($config));

        $serviceProvider = new ServiceProvider(
            $mockApp = Mockery::mock(Application::class)
        );
        $mockApp->shouldReceive('routesAreCached')->andReturnFalse();

        $spyRoute = Mockery::spy('overload:' . Route::class);
        $spyRoute->shouldReceive('group')->with(Mockery::any(), Mockery::on(function ($callable) {
            $callable();
        }));
        $spyRoute->shouldReceive('get')->andReturnSelf();

        $serviceProvider->boot();

        $spyRoute->shouldHaveReceived('group', [['prefix' => 'auth'], Mockery::any()]);
        $spyRoute->shouldHaveReceived('get', ['signIn', AuthenticateController::class . '@signIn']);
        $spyRoute->shouldHaveReceived('get', ['callback', AuthenticateController::class . '@callback']);
        $spyRoute->shouldHaveReceived('get', ['renew', AuthenticateController::class . '@renew']);
        $spyRoute->shouldHaveReceived('get', ['signOut', AuthenticateController::class . '@signOut']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox 設定にrouteが存在しない場合、認証系エンドポイントをルート設定に追加しない
     */
    public function notSetRoutingWhenLoadServiceProvider()
    {
        $config = [
            'cognito.route' => [],
        ];

        $mockContainer = Mockery::mock('overload:' . Container::class);
        $mockContainer->shouldReceive('getInstance')->andReturnSelf();
        $mockContainer->shouldReceive('configPath')->andReturn('/config/path');
        $mockContainer->shouldReceive('make')->with('path.config')->andReturn('/path/config');
        $mockContainer->shouldReceive('make')->with('config', [])->andReturn($config = new Config($config));

        $serviceProvider = new ServiceProvider(
            $mockApp = Mockery::mock(Application::class)
        );
        $mockApp->shouldReceive('routesAreCached')->andReturnFalse();

        $spyRoute = Mockery::spy('overload:' . Route::class);
        $spyRoute->shouldReceive('group')->with(Mockery::any(), Mockery::on(function ($callable) {
            $callable();
        }));
        $spyRoute->shouldReceive('get')->andReturnSelf();

        $serviceProvider->boot();

        $spyRoute->shouldNotHaveReceived('group', [['prefix' => 'auth'], Mockery::any()]);
        $spyRoute->shouldNotHaveReceived('get', ['signIn', AuthenticateController::class . '@signIn']);
        $spyRoute->shouldNotHaveReceived('get', ['callback', AuthenticateController::class . '@callback']);
        $spyRoute->shouldNotHaveReceived('get', ['renew', AuthenticateController::class . '@renew']);
        $spyRoute->shouldNotHaveReceived('get', ['signOut', AuthenticateController::class . '@signOut']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox route設定にprefixが存在する場合、認証系エンドポイントのprefixに設定された値を利用する
     */
    public function setEndpointPrefixWhenContainsPrefixInConfig()
    {
        $config = [
            'cognito.route' => [
                'prefix' => 'hogehogehoge'
            ],
        ];

        $mockContainer = Mockery::mock('overload:' . Container::class);
        $mockContainer->shouldReceive('getInstance')->andReturnSelf();
        $mockContainer->shouldReceive('configPath')->andReturn('/config/path');
        $mockContainer->shouldReceive('make')->with('path.config')->andReturn('/path/config');
        $mockContainer->shouldReceive('make')->with('config', [])->andReturn($config = new Config($config));

        $serviceProvider = new ServiceProvider(
            $mockApp = Mockery::mock(Application::class)
        );
        $mockApp->shouldReceive('routesAreCached')->andReturnFalse();

        $spyRoute = Mockery::spy('overload:' . Route::class);
        $spyRoute->shouldReceive('group')->with(Mockery::any(), Mockery::on(function ($callable) {
            $callable();
        }));
        $spyRoute->shouldReceive('get')->andReturnSelf();

        $serviceProvider->boot();

        $spyRoute->shouldHaveReceived('group', [['prefix' => 'hogehogehoge'], Mockery::any()]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox route設定にprefixが存在しない場合、認証系エンドポイントのprefixにauthが指定される
     */
    public function setEndpointDefaultPrefixWhenNotContainsPrefixInConfig()
    {
        $config = [
            'cognito.route' => [
                'test' => 'test',
            ],
        ];

        $mockContainer = Mockery::mock('overload:' . Container::class);
        $mockContainer->shouldReceive('getInstance')->andReturnSelf();
        $mockContainer->shouldReceive('configPath')->andReturn('/config/path');
        $mockContainer->shouldReceive('make')->with('path.config')->andReturn('/path/config');
        $mockContainer->shouldReceive('make')->with('config', [])->andReturn($config = new Config($config));

        $serviceProvider = new ServiceProvider(
            $mockApp = Mockery::mock(Application::class)
        );
        $mockApp->shouldReceive('routesAreCached')->andReturnFalse();

        $spyRoute = Mockery::spy('overload:' . Route::class);
        $spyRoute->shouldReceive('group')->with(Mockery::any(), Mockery::on(function ($callable) {
            $callable();
        }));
        $spyRoute->shouldReceive('get')->andReturnSelf();

        $serviceProvider->boot();

        $spyRoute->shouldHaveReceived('group', [['prefix' => 'auth'], Mockery::any()]);
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
