<?php


namespace Adcate\CognitoAuth\Repository;


interface OAuthStateRepository
{
    public function exists(string $state): bool;

    public function put(string $state): void;
}
