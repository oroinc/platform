<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Detects the login source by given token and auth exception.
 */
interface LoginSourceProviderForFailedRequestInterface
{
    /**
     * Returns the login source by given token and auth exception.
     */
    public function getLoginSourceForFailedRequest(TokenInterface $token, \Exception $exception): ?string;
}
