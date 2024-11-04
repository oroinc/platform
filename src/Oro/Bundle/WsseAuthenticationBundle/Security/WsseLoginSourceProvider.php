<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\UserBundle\Security\LoginSourceProviderForFailedRequestInterface;
use Oro\Bundle\WsseAuthenticationBundle\Security\Core\Authentication\WsseAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

/**
 * Detects the WSSE login source.
 */
class WsseLoginSourceProvider implements LoginSourceProviderForFailedRequestInterface
{
    #[\Override]
    public function getLoginSourceForFailedRequest(
        AuthenticatorInterface $authenticator,
        \Exception $exception
    ): ?string {
        if (is_a($authenticator, WsseAuthenticator::class)) {
            return 'wsse';
        }

        return null;
    }
}
