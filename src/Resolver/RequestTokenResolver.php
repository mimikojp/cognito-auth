<?php


namespace Adcate\CognitoAuth\Resolver;


use Illuminate\Http\Request;

class RequestTokenResolver
{
    public function resolve(Request $request)
    {
        return $request->bearerToken();
    }
}
