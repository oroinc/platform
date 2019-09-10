<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * The factory to create UsernamePasswordOrganizationToken.
 */
class UsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($user, $credentials, $providerKey, Organization $organization, array $roles = [])
    {
        return new UsernamePasswordOrganizationToken(
            $user,
            $credentials,
            $providerKey,
            $organization,
            $roles
        );
    }
}
