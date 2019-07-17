<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

/**
 * Creates WsseToken with needed data
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
