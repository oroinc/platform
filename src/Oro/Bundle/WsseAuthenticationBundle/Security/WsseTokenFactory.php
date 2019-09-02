<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

/**
 * The factory to create WsseToken.
 */
class WsseTokenFactory implements WsseTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($user, $credentials, $providerKey, array $roles = [])
    {
        return new WsseToken($user, $credentials, $providerKey, $roles);
    }
}
