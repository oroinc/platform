<?php

namespace Oro\Bundle\UserBundle\Security;

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
