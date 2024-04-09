<?php


namespace Adcate\CognitoAuth\Auth\Validator;


use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Checker\IssuedAtChecker;

class ClaimValidator
{
    /**
     * @param array $payload
     * @param string[] $audiences
     * @return bool
     */
    public function isValidAudience(array $payload, ...$audiences): bool
    {
        try {
            $checkers = collect($audiences)->map(function (string $audience) {
                return new AudienceChecker($audience);
            })->toArray();
            $audienceChecker = new ClaimCheckerManager($checkers);
            $audienceChecker->check($payload);
            return true;
        } catch (InvalidClaimException $e) {
            Log::debug('Not match the audience.', [
                'value' => $e->getValue(),
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('Error has occurred when check audience of jwt.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return false;
        }
    }

    public function beforeExpiration(array $payload, int $drift = 0): bool
    {
        try {
            $audienceChecker = new ClaimCheckerManager([new ExpirationTimeChecker($drift)]);
            $audienceChecker->check($payload);
            return true;
        } catch (InvalidClaimException $e) {
            Log::warning('Token has been expired.', [
                'message' => $e->getMessage(),
                'value' => $e->getValue(),
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('Error has occurred when check expiration of jwt.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return false;
        }
    }

    public function isValidIssuer(array $payload, string $issuer): bool
    {
        $iss = Arr::get($payload, 'iss', '');
        if ($iss !== $issuer) {
            Log::warning('Not match the issuer.', [
                'expected' => $issuer,
            ]);
            return false;
        }
        return true;
    }

    public function afterIssuedAt(array $payload, int $drift = 0): bool
    {
        try {
            $checker = new IssuedAtChecker($drift);
            $iatChecker = new ClaimCheckerManager([$checker]);
            $iatChecker->check($payload);
            return true;
        } catch (InvalidClaimException $e) {
            Log::warning('Issue date is incorrect.', [
                'message' => $e->getMessage(),
                'value' => $e->getValue(),
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('Error has occurred when check issued_at of jwt.', [
                'reason' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return false;
        }
    }
}
