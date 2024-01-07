<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The factory to create UsernamePasswordOrganizationToken.
 */
class UsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    public function create(
        AbstractUser $user,
        $firewallName,
        Organization $organization,
        array $roles = []
    ): TokenInterface {
        return new UsernamePasswordOrganizationToken(
            $user,
            $firewallName,
            $organization,
            $roles
        );
    }
}
