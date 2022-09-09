<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Represents a service to get a security token for a message.
 */
interface SecurityTokenProviderInterface
{
    public function getToken(): ?TokenInterface;
}
