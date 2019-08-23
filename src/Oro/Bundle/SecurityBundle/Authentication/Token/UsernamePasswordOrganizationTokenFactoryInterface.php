<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * An interface for factories to create UsernamePasswordOrganizationToken.
 */
interface UsernamePasswordOrganizationTokenFactoryInterface
{
    /**
     * @param string       $user
     * @param string       $credentials
     * @param string       $providerKey
     * @param Organization $organization
     * @param array        $roles
     *
     * @return UsernamePasswordOrganizationToken
     */
    public function create($user, $credentials, $providerKey, Organization $organization, array $roles = []);
}
