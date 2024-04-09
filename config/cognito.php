<?php

$oauthApiBaseUri = env('COGNITO_OAUTH_API_BASE_URI', 'https://adcate-userpool.auth.ap-northeast-1.amazoncognito.com');

return [
    'route' => [
        'prefix' => 'auth',
    ],

    'group' => env('COGNITO_GROUP'),
    'region' => env('COGNITO_REGION', 'ap-northeast-1'),

    'redirects' => [
        'authenticate' => env('COGNITO_REDIRECT_AUTHENTICATE'),
    ],

    'provider' => [
        'redirectUri' => env('COGNITO_REDIRECT_URI'),
        'clientId' => env('COGNITO_CLIENT_ID'),
        'clientSecret' => env('COGNITO_CLIENT_SECRET', ''),
        'userPoolId' => env('COGNITO_USER_POOL_ID'),
        'urlAuthorize' => $oauthApiBaseUri . '/oauth2/authorize',
        'urlAccessToken' => $oauthApiBaseUri . '/oauth2/token',
        'urlResourceOwnerDetails' => $oauthApiBaseUri . '/oauth2/userInfo',
        'urlLogout' => $oauthApiBaseUri . '/logout',
    ],
];
