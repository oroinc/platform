<?php

namespace Oro\Bundle\UserBundle\Security;

interface WsseTokenFactoryInterface
{
    /**
     * @param array $roles
     * @return WsseToken
     */
    public function create(array $roles = []);
}
