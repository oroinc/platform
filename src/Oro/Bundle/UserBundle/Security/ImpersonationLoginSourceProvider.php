<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Exception\ImpersonationAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

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
    public function getLoginSourceForFailedRequest(
        AuthenticatorInterface $authenticator,
        \Exception $exception
    ): ?string {
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
