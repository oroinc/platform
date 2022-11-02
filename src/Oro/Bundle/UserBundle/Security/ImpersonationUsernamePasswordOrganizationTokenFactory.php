<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;

/**
 * Factory for creating ImpersonatedUsernamePasswordOrganizationToken.
 */
class ImpersonationUsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($user, $credentials, $providerKey, Organization $organization, array $roles = [])
    {
        return new ImpersonationUsernamePasswordOrganizationToken(
            $user,
            $credentials,
            $providerKey,
            $organization,
            $roles
        );
    }
}
