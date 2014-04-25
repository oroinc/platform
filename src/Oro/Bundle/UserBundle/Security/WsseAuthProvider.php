<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Provider\Provider;

/**
 * Class WsseAuthProvider
 * This override needed to use random generated API key for WSSE auth instead regular user password.
 * In order to prevent usage of user password in third party software.
 * In case if not ORO user is used this provider fallback to native behavior.
 *
 * @package Oro\Bundle\UserBundle\Security
 */
class WsseAuthProvider extends Provider
{
    /**
     * {@inheritdoc}
     */
    protected function getSecret(UserInterface $user)
    {
        if ($user instanceof AdvancedApiUserInterface) {
            return $user->getApiKey();
        }

        return parent::getSecret($user);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSalt(UserInterface $user)
    {
        if ($user instanceof AdvancedApiUserInterface) {
            return '';
        }

        return parent::getSalt($user);
    }
}
