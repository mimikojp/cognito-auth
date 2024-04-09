<?php

namespace Adcate\CognitoAuth\Auth\Validator;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;

class ClaimValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        Log::spy();
    }

    /**
     * @test
     * @testdox JWTのオーディエンスが期待する利用者識別子群に含まれている場合trueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::isValidAudience
     */
    function returnTrueIfIncludeTheAudience()
    {
        // setup
        $payload = [
            'aud' => 'expected-audience',
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->isValidAudience($payload, 'test-audience', 'adcate-user', 'expected-audience');

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox JWTのオーディエンスが期待する利用者識別子群に含まれていない場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::isValidAudience
     */
    function returnFalseIfNotIncludeTheAudience()
    {
        // setup
        $payload = [
            'aud' => 'invalid-audience',
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->isValidAudience($payload, 'test-audience', 'adcate-user', 'expected-audience');

        // then
        $this->assertFalse($actual);
    }
    /**
     * @test
     * @testdox 有効期限より前の場合trueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::beforeExpiration
     */
    function returnTrueIfBeforeTheExpirationTime()
    {
        // setup
        $payload = [
            'exp' => time() + 300,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->beforeExpiration($payload);

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox 有効期限を過ぎている場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::beforeExpiration
     */
    function returnFalseIfAfterTheExpirationTime()
    {
        // setup
        $payload = [
            'exp' => time() - 100,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->beforeExpiration($payload);

        // then
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 有効期限を過ぎている場合でも猶予時間が設定されており、その範囲内に含まれていればtrueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::beforeExpiration
     */
    function returnTrueIfIncludeInTheSumOfExpirationTimeAndDrift()
    {
        // setup
        $payload = [
            'exp' => time() - 100,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->beforeExpiration($payload, 200);

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox 有効期限を過ぎている場合で猶予時間が設定されているが、その範囲外の場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::beforeExpiration
     */
    function returnFalseIfOverTheSumOfExpirationTimeAndDrift()
    {
        // setup
        $payload = [
            'exp' => time() - 200,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->beforeExpiration($payload, 100);

        // then
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox JWTの発行者をチェックし、一致した場合trueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::isValidIssuer
     */
    function returnTrueIfMatchIsFoundIssuer()
    {
        // setup
        $payload = [
            'iss' => 'expected-issuer',
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->isValidIssuer($payload, 'expected-issuer');

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox JWTの発行者をチェックし、一致しなかった場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::isValidIssuer
     */
    function returnFalseIfMatchIsNotFoundIssuer()
    {
        // setup
        $payload = [
            'iss' => 'invalid-issuer',
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->isValidIssuer($payload, 'expected-issuer');

        // then
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 発行時刻が過去の場合trueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::afterIssuedAt
     */
    function returnTrueIfPastIssuedAt()
    {
        // setup
        $payload = [
            'iat' => time() - 300,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->afterIssuedAt($payload);

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox 発行時刻が未来の場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::afterIssuedAt
     */
    function returnFalseIfIssuedAtIsInFuture()
    {
        // setup
        $payload = [
            'iat' => time() + 300,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->afterIssuedAt($payload);

        // then
        $this->assertFalse($actual);
    }

    /**
     * @test
     * @testdox 発行時刻が未来の場合でも猶予時間が設定されており、その範囲内に含まれていればtrueを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::afterIssuedAt
     */
    function returnTrueIfIncludeInTheSumOfIssuedAtAndDrift()
    {
        // setup
        $payload = [
            'iat' => time() + 300,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->afterIssuedAt($payload, 500);

        // then
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @testdox 発行時刻が未来の場合で猶予時間が設定されているが、その範囲外の場合falseを返す
     * @covers \Adcate\CognitoAuth\Auth\Validator\ClaimValidator::afterIssuedAt
     */
    function returnFalseIfOverTheSumOfIssuedAtAndDrift()
    {
        // setup
        $payload = [
            'iat' => time() + 300,
        ];

        // when
        $validator = new ClaimValidator();
        $actual = $validator->afterIssuedAt($payload, 100);

        // then
        $this->assertFalse($actual);
    }
}
