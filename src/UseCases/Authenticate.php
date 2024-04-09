<?php


namespace Adcate\CognitoAuth\UseCases;


use Adcate\CognitoAuth\Auth\CognitoAuthProvider;
use Adcate\CognitoAuth\Repository\OAuthStateRepository;
use Adcate\CognitoAuth\Repository\RefreshTokenRepository;
use Adcate\CognitoAuth\Resolver\AuthenticatedResponseResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Authenticate
{
    /**
     * @var OAuthStateRepository
     */
    private $stateRepository;
    /**
     * @var CognitoAuthProvider
     */
    private $provider;
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;
    /**
     * @var AuthenticatedResponseResolver
     */
    private $responseResolver;

    public function __construct(CognitoAuthProvider $provider, OAuthStateRepository $stateRepository,
                                RefreshTokenRepository $refreshTokenRepository, AuthenticatedResponseResolver $responseResolver)
    {
        $this->stateRepository = $stateRepository;
        $this->provider = $provider;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->responseResolver = $responseResolver;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws AuthenticationException
     */
    public function invoke(Request $request)
    {
        $state = $request->get('state');
        if (empty($state) || !$this->stateRepository->exists($state)) {
            Log::error('Could not find the state in the repository.', [
                'state' => $state,
            ]);
            throw new AuthenticationException();
        }

        // Try to get an access token (using the authorization coe grant)
        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);
        } catch (IdentityProviderException $e) {
            Log::error('Failed to get the access token.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->getResponseBody(),
            ]);
            throw new AuthenticationException();
        }

        $accessToken = $token->getToken();
        $this->refreshTokenRepository->put($accessToken, $token->getRefreshToken());
        $redirectTo = config('cognito.redirects.authenticate');
        return $this->responseResolver->resolve($redirectTo, $accessToken);
    }
}
