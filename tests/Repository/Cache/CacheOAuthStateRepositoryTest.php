<?php

namespace Adcate\CognitoAuth\Repository\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class CacheOAuthStateRepositoryTest
 * @package Adcate\CognitoAuth\Repository\Cache
 * @runTestsInSeparateProcesses
 */
class CacheOAuthStateRepositoryTest extends TestCase
{
    /**
     * @test
     * @testdox Stateはキャッシュに保存される
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheOAuthStateRepository::put
     */
    public function stateSaveToCache()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheOAuthStateRepository();
        $repository->put('state');

        $spyCache->shouldHaveReceived('put', ['auth.beforeAuthorize.state.state', 1, Mockery::any()]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox Stateは5分間キャッシュされる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheOAuthStateRepository::put
     */
    public function stateLifeTimeIsFiveMinutes()
    {
        Carbon::setTestNow('2019-09-06 15:00:00');

        $spyCache = Cache::spy();
        $spyCache->shouldReceive('put');

        $repository = new CacheOAuthStateRepository();
        $repository->put('state');

        $spyCache->shouldHaveReceived('put', [Mockery::any(), Mockery::any(), Mockery::on(function (Carbon $actual) {
            return $actual->format('Y-m-d H:i:s') === '2019-09-06 15:05:00';
        })]);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox Stateがキャッシュに保存されているかチェックできる
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheOAuthStateRepository::exists
     */
    public function checkStateExists()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('has')->with('auth.beforeAuthorize.state.state')->andReturnFalse();
        $spyCache->shouldReceive('forget');

        $repository = new CacheOAuthStateRepository();
        $this->assertFalse($repository->exists('state'));

        $spyCache->shouldHaveReceived('has', ['auth.beforeAuthorize.state.state']);
    }

    /**
     * @test
     * @testdox キャッシュにStateが保存されている場合、チェック後にキャッシュされたStateは削除される
     * @covers \Adcate\CognitoAuth\Repository\Cache\CacheOAuthStateRepository::exists
     */
    public function removeCacheIfExistsState()
    {
        $spyCache = Cache::spy();
        $spyCache->shouldReceive('has')->with('auth.beforeAuthorize.state.state')->andReturnTrue();
        $spyCache->shouldReceive('forget');

        $repository = new CacheOAuthStateRepository();
        $repository->exists('state');

        $spyCache->shouldHaveReceived('forget', ['auth.beforeAuthorize.state.state']);
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
