<?php


namespace Adcate\CognitoAuth\Http\Middleware;


use Adcate\CognitoAuth\Auth\TokenHelper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;

class VerifyToken
{
    /**
     * @var TokenHelper
     */
    private $tokenHelper;

    public function __construct(TokenHelper $tokenHelper)
    {
        $this->tokenHelper = $tokenHelper;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, $next)
    {
        $token = $request->bearerToken();
        if ($token === null) {
            $token = $request->query('token');
        }
        Log::debug('Verify token.', [
            'token' => $token,
        ]);

        if ($token === null) {
            throw new AuthenticationException('Could not get token.');
        }

        $claim = $this->tokenHelper->validate($token);
        if ($claim === null) {
            throw new AuthenticationException('The token is not valid.');
        }

        if (!$this->tokenHelper->isAuthorized($claim)) {
            throw new AuthenticationException('This user is not authorized.');
        }

        if ($this->tokenHelper->isExpired($claim)) {
            throw new AuthenticationException('The token is expired.');
        }

        return $next($request);
    }
}
