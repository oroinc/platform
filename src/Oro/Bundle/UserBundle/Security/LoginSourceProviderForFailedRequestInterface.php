<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

/**
 * Detects the login source by given token and auth exception.
 */
interface LoginSourceProviderForFailedRequestInterface
{
    /**
     * Returns the login source by given token and auth exception.
     */
    public function getLoginSourceForFailedRequest(
        AuthenticatorInterface $authenticator,
        \Exception $exception
    ): ?string;
}
