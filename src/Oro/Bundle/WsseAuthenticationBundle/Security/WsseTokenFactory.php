<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * The factory to create WsseToken.
 */
class WsseTokenFactory implements WsseTokenFactoryInterface
{
    #[\Override]
    public function create(AbstractUser $user, $firewallName, array $roles = []): WsseToken
    {
        return new WsseToken($user, $firewallName, $roles);
    }
}
