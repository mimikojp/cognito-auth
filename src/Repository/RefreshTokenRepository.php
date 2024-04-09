<?php


namespace Adcate\CognitoAuth\Repository;

interface RefreshTokenRepository
{
    public function get(string $accessToken): ?string;

    public function put(string $accessToken, string $refreshToken): void;

    public function delete(string $accessToken): void;
}
