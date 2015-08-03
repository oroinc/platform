<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Guesser;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\OrganizationAwareUserInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class UserOrganizationGuesser
{
    /**
     * Guess organization to login into. Basically for single organization scenario it will be always the same
     * organization where user was created.
     *
     * @param OrganizationAwareUserInterface $user
     * @param TokenInterface $token
     *
     * @return null|Organization
     */
    public function guess(OrganizationAwareUserInterface $user, TokenInterface $token)
    {
        if ($token instanceof OrganizationContextTokenInterface && $token->getOrganizationContext()) {
            return $token->getOrganizationContext();
        }

        $activeOrganizations = $user->getOrganizations(true);
        $creatorOrganization = $user->getOrganization();

        return $activeOrganizations->contains($creatorOrganization)
            ? $creatorOrganization
            : $activeOrganizations->first();
    }
}
