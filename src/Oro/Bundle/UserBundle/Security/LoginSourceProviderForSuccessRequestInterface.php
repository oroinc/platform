<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Detects the login source by given token.
 */
interface LoginSourceProviderForSuccessRequestInterface
{
    /**
     * Returns the login source by given token.
     */
    public function getLoginSourceForSuccessRequest(TokenInterface $token): ?string;
}
