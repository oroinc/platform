<?php

namespace Oro\Bundle\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Exception that throws during filed impersonation authentication.
 */
class ImpersonationAuthenticationException extends CustomUserMessageAuthenticationException implements
    UserHolderExceptionInterface
{
    private $user;

    /**
     * {@inheritDoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritDoc}
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }
}
