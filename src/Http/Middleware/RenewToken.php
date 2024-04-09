<?php


namespace Adcate\CognitoAuth\Http\Middleware;


use Adcate\CognitoAuth\Auth\TokenHelper;
use Adcate\CognitoAuth\Resolver\RenewalTokenResponseResolver;
use Adcate\CognitoAuth\Resolver\RequestTokenResolver;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;

class RenewToken
{
    /**
     * @var TokenHelper
     */
    private $tokenHelper;
    /**
     * @var RenewalTokenResponseResolver
     */
    private $renewalTokenResponseResolver;
    /**
     * @var RequestTokenResolver
     */
    private $requestTokenResolver;

    public function __construct(TokenHelper $tokenHelper, RequestTokenResolver $requestTokenResolver,
                                RenewalTokenResponseResolver $renewalTokenResponseResolver)
    {
        $this->tokenHelper = $tokenHelper;
        $this->renewalTokenResponseResolver = $renewalTokenResponseResolver;
        $this->requestTokenResolver = $requestTokenResolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $token = $this->requestTokenResolver->resolve($request);
        Log::debug('Check token expiration.', [
            'token' => $token,
        ]);

        if ($token === null) {
            throw new AuthenticationException();
        }

        $claim = $this->tokenHelper->validate($token);
        if ($claim === null)
        {
            throw new AuthenticationException();
        }

        if (!$this->tokenHelper->isAuthorized($claim)) {
            throw new AuthenticationException('This user is not authorized.');
        }

        $renewToken = null;
        if ($this->tokenHelper->isExpired($claim)) {
            $renewToken = $this->tokenHelper->renew($token);
        }

        $response = $next($request);
        if (!$renewToken)
        {
            Log::debug('Unnecessary renew the token.', [
                'token' => $token,
            ]);
            return $response;
        }

        Log::debug('Token renewal.', [
            'token' => $token,
        ]);
        return $this->renewalTokenResponseResolver->resolve($response, $renewToken->getToken());
    }
}
