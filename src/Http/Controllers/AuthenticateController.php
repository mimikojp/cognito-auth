<?php


namespace Adcate\CognitoAuth\Http\Controllers;


use Adcate\CognitoAuth\UseCases\Authenticate;
use Adcate\CognitoAuth\UseCases\SignIn;
use Adcate\CognitoAuth\UseCases\SignOut;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthenticateController extends Controller
{
    public function signIn(SignIn $useCase)
    {
        return $useCase->invoke();
    }

    public function callback(Request $request, Authenticate $useCase)
    {
        return $useCase->invoke($request);
    }

    public function renew(Request $request)
    {
        // 実際のトークン更新処理は、routerで指定しているRenewBearerTokenミドルウェアで処理しているため、
        // 追加で処理が必要な場合、ここで処理を行う。
    }

    public function signOut(Request $request, SignOut $useCase)
    {
        $useCase->invoke($request->bearerToken());
    }
}
