<?php

namespace Oro\Bundle\UserBundle\Security;

/**
 * Creates WsseToken with needed data
 */
interface WsseTokenFactoryInterface
{
    /**
     * @param string|object            $user        The username (like a nickname, email address, etc.),
     *                                          or UserInterface instance or an object implementing a __toString method
     * @param mixed                    $credentials This usually is the password of the user
     * @param string                   $providerKey The provider key
     * @param (RoleInterface|string)[] $roles       An array of roles
     * @return WsseToken
     */
    public function create($user, $credentials, $providerKey, array $roles = []);
}
