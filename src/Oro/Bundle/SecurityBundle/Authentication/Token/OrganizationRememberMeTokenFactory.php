<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationRememberMeTokenFactory implements OrganizationRememberMeTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(UserInterface $user, $providerKey, $key, Organization $organizationContext)
    {
        $authenticatedToken = new OrganizationRememberMeToken(
            $user,
            $providerKey,
            $key,
            $organizationContext
        );

        return $authenticatedToken;
    }
}
