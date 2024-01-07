<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * An interface for factories to create WsseToken.
 */
interface WsseTokenFactoryInterface
{
    public function create(AbstractUser $user, string $firewallName, array $roles = []): WsseToken;
}
