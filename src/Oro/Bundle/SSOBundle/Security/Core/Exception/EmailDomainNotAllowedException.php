<?php

namespace Oro\Bundle\SSOBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class EmailDomainNotAllowedException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Invalid credentials.';
    }
}
