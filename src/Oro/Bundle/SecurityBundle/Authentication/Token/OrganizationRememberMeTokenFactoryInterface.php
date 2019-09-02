<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * An interface for factories to create OrganizationRememberMeToken.
 */
interface OrganizationRememberMeTokenFactoryInterface
{
    /**
     * @param UserInterface $user
     * @param string        $providerKey
     * @param string        $key
     * @param Organization  $organization
     *
     * @return OrganizationRememberMeToken
     */
    public function create(UserInterface $user, $providerKey, $key, Organization $organization);
}
