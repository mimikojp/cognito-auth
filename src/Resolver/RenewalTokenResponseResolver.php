<?php


namespace Adcate\CognitoAuth\Resolver;


use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as AbstractResponse;

class RenewalTokenResponseResolver
{
    public function resolve(Response $response, string $token): AbstractResponse
    {
        $response->headers->set('X-Renew-Authorization', $token);
        return $response;
    }
}
