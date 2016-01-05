<?php

namespace Oro\Bundle\UserBundle\Security;

class WsseTokenFactory implements WsseTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(array $roles = [])
    {
        return new WsseToken($roles);
    }
}
