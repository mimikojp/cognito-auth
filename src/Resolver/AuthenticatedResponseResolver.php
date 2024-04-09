<?php


namespace Adcate\CognitoAuth\Resolver;


use Symfony\Component\HttpFoundation\Response;

class AuthenticatedResponseResolver
{
    public function resolve(string $redirectTo, string $token): Response
    {
        return response()->redirectTo($redirectTo . '#' . $token);
    }
}
