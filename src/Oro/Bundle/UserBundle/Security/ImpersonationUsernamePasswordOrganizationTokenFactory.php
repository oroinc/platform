<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Factory for creating ImpersonatedUsernamePasswordOrganizationToken.
 */
class ImpersonationUsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    public function create(
        AbstractUser $user,
        string $firewallName,
        Organization $organization,
        array $roles = []
    ): TokenInterface {
        return new ImpersonationUsernamePasswordOrganizationToken(
            $user,
            $firewallName,
            $organization,
            $roles
        );
    }
}
