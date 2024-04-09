<?php


namespace Adcate\CognitoAuth\UseCases;


use Adcate\CognitoAuth\Repository\RefreshTokenRepository;

class SignOut
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function invoke(string $accessToken)
    {
        $this->refreshTokenRepository->delete($accessToken);
    }
}
