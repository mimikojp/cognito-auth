<?php

namespace Adcate\CognitoAuth\Auth;

use Adcate\CognitoAuth\Auth\Validator\SignatureValidator;
use Adcate\CognitoAuth\Repository\RefreshTokenRepository;
use Adcate\CognitoAuth\Auth\Validator\ClaimValidator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class TokenHelperTest
 * @package Adcate\CognitoAuth\Auth
 * @runTestsInSeparateProcesses
 */
class TokenHelperTest extends TestCase
{
    private $spyConfig;

    protected function setUp(): void
    {
        Log::spy();

        $this->spyConfig = Config::spy();
        $this->spyConfig->shouldReceive('get')->with('cognito.provider.clientId')->andReturn('client-id');
        $this->spyConfig->shouldReceive('get')->with('cognito.group')->andReturn('cognito-group');
    }

    /**
     * @test
     * @testdox 認証トークンがデシリアライズできなかった場合、nullが返却される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::validate
     */
    public function returnNullWhenCouldNotDeserialize()
    {
        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            $mockSerializerManager = Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $mockSerializerManager->shouldReceive('unserialize')->andThrow(InvalidArgumentException::class);

        $actual = $helper->validate('token');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @testdox JWSの署名に不正があった場合、nullが返却される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::validate
     */
    public function returnNullWhenJwsIsInvalid()
    {
        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            $mockSerializerManager = Mockery::mock(JWSSerializerManager::class),
            $mockSignatureValidator = Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $mockSerializerManager->shouldReceive('unserialize')->andReturn(
            $mockJws = Mockery::mock(JWS::class)
        );
        $mockSignatureValidator->shouldReceive('isValid')->with($mockJws)->andReturnFalse();

        $actual = $helper->validate('token');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @testdox Audienceに違反があった場合、nullが返却される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::validate
     */
    public function returnNullWhenAudienceIsInvalid()
    {
        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            $mockSerializerManager = Mockery::mock(JWSSerializerManager::class),
            $mockSignatureValidator = Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            $mockValidator = Mockery::mock(ClaimValidator::class)
        );

        $mockSerializerManager->shouldReceive('unserialize')->andReturn(
            $mockJws = Mockery::mock(JWS::class)
        );
        $mockJws->shouldReceive('getPayload')->andReturn('{"aud": "invalid"}');
        $mockSignatureValidator->shouldReceive('isValid')->andReturnTrue();

        $mockValidator->expects('isValidAudience')->andReturnFalse();

        $actual = $helper->validate('token');
        $this->assertNull($actual);
    }

    /**
     * @test
     * @testdox トークンの妥当性が確認できた場合、Claimが連想配列で返却される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::validate
     */
    public function returnArrayAsClaimWhenTokenIsValid()
    {
        $mockConverter = Mockery::mock('overload:' . JsonConverter::class);
        $mockConverter->shouldReceive('decode')->andreturn(['aud' => 'client-id', 'sub' => 'uuid']);

        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            $mockSerializerManager = Mockery::mock(JWSSerializerManager::class),
            $mockSignatureValidator = Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            $mockValidator = Mockery::mock(ClaimValidator::class)
        );
        $mockValidator->expects('isValidAudience')->andReturnTrue();

        $mockSerializerManager->shouldReceive('unserialize')->andReturn(
            $mockJws = Mockery::mock(JWS::class)
        );
        $mockJws->shouldReceive('getPayload')->andReturn('');
        $mockSignatureValidator->shouldReceive('isValid')->andReturnTrue();

        $actual = $helper->validate('token');
        $this->assertEquals(['aud' => 'client-id', 'sub' => 'uuid'] , $actual);
    }

    /**
     * @test
     * @testdox Claim内の所属グループに環境変数指定のグループ名が含まれていない場合、認可されていないものとみなす
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::isAuthorized
     */
    public function regardUserAsUnauthorized()
    {
        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $actual = $helper->isAuthorized(['cognito:group' => ['unexpected-group']]);
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox Claim内の所属グループに環境変数指定のグループ名が含まれている場合、認可されているものとみなす
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::isAuthorized
     */
    public function regardUserAsAuthorized()
    {
        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $actual = $helper->isAuthorized(['cognito:group' => ['cognito-group']]);
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 環境変数で所属グループ名が指定されていない場合、グループに所属していなくとも認可されているものとみなす
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::isAuthorized
     */
    public function regardUserAsAuthorizedAlways()
    {
        $this->spyConfig->shouldReceive('get')->with('cognito.group')->andReturnNull();

        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $actual = $helper->isAuthorized(['cognito:group' => []]);
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox バリデーターがfalseを返した時、トークンは無効とみなす
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::isExpired
     */
    public function regardTokenAsInvalid()
    {
        $mockChecker = Mockery::mock('overload:' . ClaimCheckerManager::class);
        $mockChecker->shouldReceive('create')->with([self::identicalTo(ExpirationTimeChecker::class)])->andReturnSelf();
        $mockChecker->shouldReceive('check')->with([])->andThrow(InvalidClaimException::class);

        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            $mockValidator = Mockery::mock(ClaimValidator::class)
        );
        $mockValidator->expects('beforeExpiration')->andReturnFalse();

        $actual = $helper->isExpired([]);
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox バリデーターがtrueを返した時、トークンは有効とみなす
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::isExpired
     */
    public function regardTokenAsValid()
    {
        $mockChecker = Mockery::mock('overload:' . ClaimCheckerManager::class);
        $mockChecker->shouldReceive('create')->andReturnSelf();
        $mockChecker->shouldReceive('check');

        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            Mockery::mock(RefreshTokenRepository::class),
            $mockValidator = Mockery::mock(ClaimValidator::class)
        );
        $mockValidator->expects('beforeExpiration')->andReturnTrue();

        $actual = $helper->isExpired([]);
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox トークン更新時、リフレッシュトークンが取得できなかった場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::renew
     */
    public function throwExceptionWhenCouldNotGetRefreshToken()
    {
        $this->expectException(AuthenticationException::class);

        $helper = new TokenHelper(
            Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            $mockRepository = Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $mockRepository->shouldReceive('get')->andReturnNull();

        $helper->renew('');
    }

    /**
     * @test
     * @testdox トークン更新時、アクセストークン取得のための通信上でエラーが発生した場合、AuthenticationExceptionがスローされる
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::renew
     */
    public function throwExceptionWhenAccidentInGetTheAccessToken()
    {
        $this->expectException(AuthenticationException::class);

        $helper = new TokenHelper(
            $mockProvider = Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            $mockRepository = Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $mockRepository->shouldReceive('get')->andReturn('refresh-token');
        $mockProvider->shouldReceive('getAccessToken')->andThrow(IdentityProviderException::class);

        $helper->renew('');
    }

    /**
     * @test
     * @testdox トークン更新時、新しいアクセストークンをキーとして元あったリフレッシュトークンが保存される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::renew
     */
    public function storeOldRefreshTokenToNewAccessToken()
    {
        $helper = new TokenHelper(
            $mockProvider = Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            $spyRepository = Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $spyRepository->shouldReceive('get')->andReturn('old-refresh-token');
        $spyRepository->shouldReceive('put');
        $mockProvider->shouldReceive('getAccessToken')->andReturn(
            $mockAccessToken = Mockery::mock(AccessTokenInterface::class)
        );
        $mockAccessToken->shouldReceive('getToken')->andReturn('new-access-token');
        $mockAccessToken->shouldReceive('getRefreshToken')->andReturn('new-refresh-token');

        $helper->renew('old-access-token');

        $spyRepository->shouldHaveReceived('put', ['new-access-token', 'old-refresh-token']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox トークン更新後、新しいアクセストークンが返却される
     * @covers \Adcate\CognitoAuth\Auth\TokenHelper::renew
     */
    public function returnNewAccessToken()
    {
        $helper = new TokenHelper(
            $mockProvider = Mockery::mock(CognitoAuthProvider::class),
            Mockery::mock(JWSSerializerManager::class),
            Mockery::mock(SignatureValidator::class),
            $spyRepository = Mockery::mock(RefreshTokenRepository::class),
            Mockery::mock(ClaimValidator::class)
        );

        $spyRepository->shouldReceive('get')->andReturn('old-refresh-token');
        $spyRepository->shouldReceive('put');
        $mockProvider->shouldReceive('getAccessToken')->andReturn(
            $mockAccessToken = Mockery::mock(AccessTokenInterface::class)
        );
        $mockAccessToken->shouldReceive('getToken')->andReturn('new-access-token');
        $mockAccessToken->shouldReceive('getRefreshToken')->andReturn('new-refresh-token');

        $actual = $helper->renew('old-access-token');
        $this->assertEquals($mockAccessToken, $actual);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
