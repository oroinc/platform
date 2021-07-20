<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Guesser;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The default implementation of the organization guesser.
 */
class OrganizationGuesser implements OrganizationGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guess(AbstractUser $user, TokenInterface $token = null): ?Organization
    {
        if ($token instanceof OrganizationAwareTokenInterface) {
            $organization = $token->getOrganization();
            if (null !== $organization) {
                return $organization;
            }
        }

        $organization = $user->getOrganization();
        if (null === $organization || !$user->isBelongToOrganization($organization, true)) {
            $organization = $this->getFirstOrganization($user->getOrganizations(true));
        }

        return $organization;
    }

    private function getFirstOrganization(Collection $organizations): ?Organization
    {
        $organization = $organizations->first();
        if (false === $organization) {
            $organization = null;
        }

        return $organization;
    }
}
