<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

interface UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * @param string $user
     * @param string $credentials
     * @param string $providerKey
     * @param Organization $organizationContext
     * @param array $roles
     * @return UsernamePasswordOrganizationToken
     */
    public function create($user, $credentials, $providerKey, Organization $organizationContext, array $roles = []);
}
