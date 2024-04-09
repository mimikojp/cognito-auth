<?php


namespace Adcate\CognitoAuth\Auth\Validator;


use Adcate\CognitoAuth\Repository\JwkSetRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;

class SignatureValidator
{
    /**
     * @var JWSVerifier
     */
    protected $JWSVerifier;
    /**
     * @var JwkSetRepository
     */
    protected $jwkSetRepository;
    /**
     * @var Client
     */
    protected $client;

    public function __construct(JWSVerifier $JWSVerifier, JwkSetRepository $jwkSetRepository, Client $client)
    {
        $this->JWSVerifier = $JWSVerifier;
        $this->jwkSetRepository = $jwkSetRepository;
        $this->client = $client;
    }

    public function isValid(JWS $jws): bool
    {
        $definition = $this->getJwkSetDefinition();
        if ($definition === null) {
            return false;
        }

        try {
            $jwkSet = JWKSet::createFromJson($definition);
        } catch (InvalidArgumentException $e) {
            Log::critical('Could not create instance of JWKSet.', [
                'definition' => $definition,
            ]);
            return false;
        }
        return $this->JWSVerifier->verifyWithKeySet($jws, $jwkSet, 0);
    }

    protected function getJwkSetDefinition(): ?string
    {
        $jwkSet = $this->jwkSetRepository->get();
        if ($jwkSet !== null) {
            Log::debug('Resolve JWT keySet from cache.');
            return $jwkSet;
        }

        try {
            ['region' => $region] = Config::get('cognito');
            ['userPoolId' => $userPoolId] = Config::get('cognito.provider');
            $jwksUrl = "https://cognito-idp.$region.amazonaws.com/$userPoolId/.well-known/jwks.json";
            $response = $this->client->request('GET', $jwksUrl);
            $jwks = $response->getBody()->getContents();
            $this->jwkSetRepository->put($jwks);

            return $jwks;
        } catch (GuzzleException $e) {
            Log::critical('Could not take jwks.json from cognito-idp.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return null;
        }
    }
}
