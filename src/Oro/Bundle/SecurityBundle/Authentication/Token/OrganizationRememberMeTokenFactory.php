<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\User\UserInterface;

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
