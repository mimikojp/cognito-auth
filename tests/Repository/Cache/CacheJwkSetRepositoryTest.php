<?php

namespace Adcate\CognitoAuth\Repository\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class CacheJwkSetRepositoryTest
 * @package Adcate\CognitoAuth\Repository\Cache
 * @runTestsInSeparateProcesses
 */
class CacheJwkSetRepositoryTest extends TestCase
{
    /**
     * @test
     * @testdox 保存済みJwkSetはキャッシュから取得できる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheJwkSetRepository::get
     */
    public function getJwksFromCache()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('get');

        $repository = new CacheJwkSetRepository();
        $repository->get();

        $spyCache->shouldHaveReceived('get', ['auth.jwkSet']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox JwkSetはキャッシュに保存される
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheJwkSetRepository::put
     */
    public function setJwksToCache()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheJwkSetRepository();
        $repository->put('jwkset-string');

        $spyCache->shouldHaveReceived('put', ['auth.jwkSet', 'jwkset-string', Mockery::any()]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox JwkSetは12時間キャッシュされる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheJwkSetRepository::put
     */
    public function jwksLifetimeIsTwentyHours()
    {
        Carbon::setTestNow('2019-09-06 03:00:00');

        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheJwkSetRepository();
        $repository->put('');

        $spyCache->shouldHaveReceived('put', [Mockery::any(), Mockery::any(), Mockery::on(function (Carbon $actual) {
            return $actual->format('Y-m-d H:i:s') === '2019-09-06 15:00:00';
        })]);
        $this->assertTrue(true);
    }
}
