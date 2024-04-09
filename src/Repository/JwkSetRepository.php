<?php


namespace Adcate\CognitoAuth\Repository;


interface JwkSetRepository
{
    public function get(): ?string;
    public function put(string $jwkSet): void;
}
