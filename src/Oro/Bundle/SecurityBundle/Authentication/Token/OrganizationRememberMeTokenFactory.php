<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The factory to create OrganizationRememberMeToken.
 */
class OrganizationRememberMeTokenFactory implements OrganizationRememberMeTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(UserInterface $user, $providerKey, $key, Organization $organization)
    {
        return new OrganizationRememberMeToken(
            $user,
            $providerKey,
            $key,
            $organization
        );
    }
}
