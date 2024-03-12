<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * An interface for factories to create UsernamePasswordOrganizationToken.
 */
interface UsernamePasswordOrganizationTokenFactoryInterface
{
    public function create(
        AbstractUser $user,
        string $firewallName,
        Organization $organization,
        array $roles = []
    ): TokenInterface;
}
