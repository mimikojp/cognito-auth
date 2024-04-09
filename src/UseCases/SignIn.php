<?php


namespace Adcate\CognitoAuth\UseCases;


use Adcate\CognitoAuth\Auth\CognitoAuthProvider;
use Adcate\CognitoAuth\Repository\OAuthStateRepository;
use Symfony\Component\HttpFoundation\Response;

class SignIn
{
    /**
     * @var CognitoAuthProvider
     */
    private $provider;
    /**
     * @var OAuthStateRepository
     */
    private $stateRepository;

    public function __construct(CognitoAuthProvider $provider, OAuthStateRepository $stateRepository)
    {
        $this->provider = $provider;
        $this->stateRepository = $stateRepository;
    }

    public function invoke(): Response
    {
        $authUrl = $this->provider->getAuthorizationUrl();
        $this->stateRepository->put($this->provider->getState());
        return redirect($authUrl);
    }
}
