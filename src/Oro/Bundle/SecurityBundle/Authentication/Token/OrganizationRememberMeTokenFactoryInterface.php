<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * An interface for factories to create OrganizationRememberMeToken.
 */
interface OrganizationRememberMeTokenFactoryInterface
{
    /**
     * @param AbstractUser  $user
     * @param string        $firewall
     * @param string        $secret
     * @param Organization  $organization
     *
     * @return OrganizationRememberMeToken
     */
    public function create(AbstractUser $user, string $firewall, string $secret, Organization $organization);
}
