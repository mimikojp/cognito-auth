<?php


namespace Adcate\CognitoAuth\Auth;


use Adcate\CognitoAuth\Auth\Validator\SignatureValidator;
use Adcate\CognitoAuth\Repository\RefreshTokenRepository;
use Adcate\CognitoAuth\Auth\Validator\ClaimValidator;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;

class TokenHelper
{
    /**
     * @var CognitoAuthProvider
     */
    private $provider;
    /**
     * @var JWSSerializerManager
     */
    private $serializerManager;
    /**
     * @var SignatureValidator
     */
    private $signatureValidator;
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;
    /**
     * @var ClaimValidator
     */
    private $claimValidator;

    public function __construct(CognitoAuthProvider $provider, JWSSerializerManager $serializerManager,
                                SignatureValidator $signatureValidator, RefreshTokenRepository $refreshTokenRepository,
                                ClaimValidator $jwtValidator)
    {
        $this->provider = $provider;
        $this->serializerManager = $serializerManager;
        $this->signatureValidator = $signatureValidator;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->claimValidator = $jwtValidator;
    }

    public function validate(string $token): ?array
    {
        try {
            $jws = $this->serializerManager->unserialize($token);
        } catch (Exception $e) {
            Log::warning('Could not deserialize jwt.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return null;
        }

        if (!$this->signatureValidator->isValid($jws)) {
            Log::warning('Failed to verify the token.', [
                'token' => $token,
            ]);
            return null;
        }

        $claim = JsonConverter::decode($jws->getPayload());

        if (!$this->claimValidator->isValidAudience($claim, Config::get('cognito.provider.clientId'))) {
            return null;
        }

        return $claim;
    }

    public function isAuthorized(array $claim): bool
    {
        $groupName = Config::get('cognito.group');
        if (!$groupName) {
            return true;
        }

        $groups = Arr::get($claim, 'cognito:groups', []);
        $belongsToGroup = in_array($groupName, $groups);
        if (!$belongsToGroup) {
            Log::warning('This user not belongs to the group.', [
                'username' => Arr::get($claim, 'username'),
                'groups' => $groups,
                'groupName' => $groupName,
            ]);
        }
        return $belongsToGroup;
    }

    public function isExpired(array $claim): bool
    {
        return !$this->claimValidator->beforeExpiration($claim);
    }

    /**
     * @param string $token
     * @return AccessTokenInterface
     * @throws AuthenticationException
     */
    public function renew(string $token): AccessTokenInterface
    {
        Log::debug('Renew token', [
            'token' => $token,
        ]);

        $refreshToken = $this->refreshTokenRepository->get($token);
        if ($refreshToken === null) {
            Log::warning('Could not get the refresh token.', [
                'access_token' => $token,
            ]);
            throw new AuthenticationException();
        }

        try {
            $accessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);
            $this->refreshTokenRepository->put($accessToken->getToken(), $refreshToken);
            return $accessToken;
        } catch (IdentityProviderException $e) {
            Log::critical('Could not refresh the access token.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->getResponseBody(),
            ]);
            throw new AuthenticationException();
        }
    }
}
