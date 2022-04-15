<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Exception\ImpersonationAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Detects the impersonation login source.
 */
class ImpersonationLoginSourceProvider implements
    LoginSourceProviderForFailedRequestInterface,
    LoginSourceProviderForSuccessRequestInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLoginSourceForFailedRequest(TokenInterface $token, \Exception $exception): ?string
    {
        if ($exception instanceof ImpersonationAuthenticationException) {
            return 'impersonation';
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getLoginSourceForSuccessRequest(TokenInterface $token): ?string
    {
        if (is_a($token, ImpersonationTokenInterface::class)) {
            return 'impersonation';
        }

        return null;
    }
}
