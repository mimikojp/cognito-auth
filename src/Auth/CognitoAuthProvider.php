<?php


namespace Adcate\CognitoAuth\Auth;


use Illuminate\Support\Facades\Config;
use League\OAuth2\Client\Provider\GenericProvider;

class CognitoAuthProvider extends GenericProvider
{
    public function __construct(array $options = [], array $collaborators = [])
    {
        $config = Config::get('cognito.provider');
        parent::__construct(array_merge($options, $config), $collaborators);
    }
}
