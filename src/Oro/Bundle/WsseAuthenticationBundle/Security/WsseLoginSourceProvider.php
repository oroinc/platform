<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\UserBundle\Security\LoginSourceProviderForFailedRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Detects the WSSE login source.
 */
class WsseLoginSourceProvider implements LoginSourceProviderForFailedRequestInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLoginSourceForFailedRequest(TokenInterface $token, \Exception $exception): ?string
    {
        if (is_a($token, WsseToken::class)) {
            return 'wsse';
        }

        return null;
    }
}
