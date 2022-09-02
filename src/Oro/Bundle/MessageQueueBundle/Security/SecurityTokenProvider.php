<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A service to get a security token for a message.
 */
class SecurityTokenProvider implements SecurityTokenProviderInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): ?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }
}
