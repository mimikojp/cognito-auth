<?php

namespace Adcate\CognitoAuth\Repository\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class CacheRefreshTokenRepositoryTest
 * @package Adcate\CognitoAuth\Repository\Cache
 * @runTestsInSeparateProcesses
 */
class CacheRefreshTokenRepositoryTest extends TestCase
{
    /**
     * @test
     * @testdox リフレッシュトークンはキャッシュに保存される
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheRefreshTokenRepository::put
     */
    public function refreshTokenSaveToCache()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheRefreshTokenRepository();
        $repository->put('access-token', 'refresh-token');

        $spyCache->shouldHaveReceived('put', ['auth.refresh_token.access-token', 'refresh-token', Mockery::any()]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox リフレッシュトークンは3日間キャッシュされる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheRefreshTokenRepository::put
     */
    public function refreshTokenLifetimeIsThreeDays()
    {
        Carbon::setTestNow('2019-09-06 14:00:00');

        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheRefreshTokenRepository();
        $repository->put('access-token', 'refresh-token');

        $spyCache->shouldHaveReceived('put', [Mockery::any(), Mockery::any(), Mockery::on(function (Carbon $actual) {
            return $actual->format('Y-m-d H:i:s') === '2019-09-09 14:00:00';
        })]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox リフレッシュトークンはアクセストークンを使って取得できる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheRefreshTokenRepository::get
     */
    public function refreshTokenTakeByAccessToken()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheRefreshTokenRepository();
        $repository->get('access-token');

        $spyCache->shouldHaveReceived('pull', ['auth.refresh_token.access-token']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox リフレッシュトークンがキャッシュから削除される
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheRefreshTokenRepository::delete
     */
    public function deleteRefreshTokenFromCache()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheRefreshTokenRepository();
        $repository->delete('access-token');

        $spyCache->shouldHaveReceived('forget', ['auth.refresh_token.access-token']);
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
