<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UsernamePasswordOrganizationTokenFactory implements UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($user, $credentials, $providerKey, Organization $organizationContext, array $roles = [])
    {
        $authenticatedToken = new UsernamePasswordOrganizationToken(
            $user,
            $credentials,
            $providerKey,
            $organizationContext,
            $roles
        );

        return $authenticatedToken;
    }
}
