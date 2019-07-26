<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * NonceExpiredException is thrown when an authentication is rejected because
 * the digest nonce has expired.
 */
class NonceExpiredException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Digest nonce has expired.';
    }
}
