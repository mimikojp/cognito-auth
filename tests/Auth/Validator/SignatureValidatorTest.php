<?php

namespace Adcate\CognitoAuth\Auth\Validator;

use Adcate\CognitoAuth\Repository\JwkSetRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class SignatureValidatorTest
 * @package Adcate\CognitoAuth\Auth\Validator
 * @runTestsInSeparateProcesses
 */
class SignatureValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        Log::spy();

        $spyConfig = Config::spy();
        $spyConfig->shouldReceive('get')->with('cognito')->andReturn(['region' => 'test-region']);
        $spyConfig->shouldReceive('get')->with('cognito.provider')->andReturn(['userPoolId' => 'test-user-pool-id']);
    }

    /**
     * @test
     * @testdox RepositoryからJWKSetが取得できた場合、CognitoIdpとは通信せずRepositoryのものからJWKSetインスタンスを生成する
     */
    public function createJwkSetInstanceFromJsonFromRepository()
    {
        $spyJwkSet = Mockery::mock('overload:' . JWKSet::class);
        $spyJwkSet->shouldReceive('createFromJson')->andReturnSelf();

        $validator = new SignatureValidator(
            $mockVerifier = Mockery::mock(JWSVerifier::class),
            $mockRepository = Mockery::mock(JwkSetRepository::class),
            $spyClient = Mockery::spy(Client::class)
        );
        $mockVerifier->shouldReceive('verifyWithKeySet')->andReturnTrue();
        $mockRepository->shouldReceive('get')->andReturn('jwkset-string');

        $this->assertTrue($validator->isValid(Mockery::mock(JWS::class)));

        $spyJwkSet->shouldHaveReceived('createFromJson', ['jwkset-string']);
        $spyClient->shouldNotReceive('request');
    }

    /**
     * @test
     * @testdox RepositoryからJWKSetが取得できない場合、CognitoIdpから取得したjwks.jsonを基にJWKSetインスタンスを生成する
     */
    public function createJwkSetInstanceFromJsonFromCognitoIdp()
    {
        $spyJwkSet = Mockery::mock('overload:' . JWKSet::class);
        $spyJwkSet->shouldReceive('createFromJson')->andReturnSelf();

        $validator = new SignatureValidator(
            $mockVerifier = Mockery::mock(JWSVerifier::class),
            $mockRepository = Mockery::mock(JwkSetRepository::class),
            $spyClient = Mockery::spy(Client::class)
        );

        $mockVerifier->shouldReceive('verifyWithKeySet')->andReturnTrue();

        $mockRepository->shouldReceive('get')->andReturnNull();
        $mockRepository->shouldReceive('put');
        $spyClient->shouldReceive('request')->andReturn(
            $mockResponse = Mockery::mock(ResponseInterface::class)
        );
        $mockResponse->shouldReceive('getBody')->andReturn(
            $mockStream = Mockery::mock(StreamInterface::class)
        );
        $mockStream->shouldReceive('getContents')->andReturn('cognito-idp-jwks.json');

        $jws = Mockery::mock(JWS::class);
        $validator->isValid($jws);

        $spyClient->shouldHaveReceived('request', [
            'GET',
            'https://cognito-idp.test-region.amazonaws.com/test-user-pool-id/.well-known/jwks.json'
        ]);
        $spyJwkSet->shouldHaveReceived('createFromJson', ['cognito-idp-jwks.json']);
        $this->assertTrue(true);
    }

    /**
     * @test
     * @testdox CognitoIdpとの通信上でエラーが発生した場合、署名は不正なものとみなす
     */
    public function regardJwsAsInvalidWhenNetworkErrorOccurred()
    {
        $validator = new SignatureValidator(
            Mockery::mock(JWSVerifier::class),
            $mockRepository = Mockery::mock(JwkSetRepository::class),
            $spyClient = Mockery::spy(Client::class)
        );

        $mockRepository->shouldReceive('get')->andReturnNull();
        $spyClient->shouldReceive('request')->andThrow(Mockery::mock(BadResponseException::class));

        $actual = $validator->isValid(
            Mockery::mock(JWS::class)
        );
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox JWKSetインスタンスが生成できなかったとき、署名は不正なものとみなす
     */
    public function regardJwsAsInvalidWhenCouldNotCreateJwkSetInstance()
    {
        $spyJwkSet = Mockery::mock('overload:' . JWKSet::class);
        $spyJwkSet->shouldReceive('createFromJson')->andThrow(InvalidArgumentException::class);

        $validator = new SignatureValidator(
            $mockVerifier = Mockery::mock(JWSVerifier::class),
            $mockRepository = Mockery::mock(JwkSetRepository::class),
            $spyClient = Mockery::spy(Client::class)
        );
        $mockVerifier->shouldReceive('verifyWithKeySet')->andReturnTrue();
        $mockRepository->shouldReceive('get')->andReturn('jwkset-string');

        $this->assertFalse($validator->isValid(Mockery::mock(JWS::class)));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
