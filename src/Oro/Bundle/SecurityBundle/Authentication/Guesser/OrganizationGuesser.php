<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Guesser;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * The default implementation of the organization guesser.
 */
class OrganizationGuesser implements OrganizationGuesserInterface
{
    #[\Override]
    public function guess(AbstractUser $user): ?Organization
    {
        $organization = $user->getOrganization();
        if (null === $organization) {
            throw new BadUserOrganizationException('The user does not have an active organization assigned to it.');
        }
        if ($user->isBelongToOrganization($organization, true)) {
            return $organization;
        }
        $firstOrganization = $this->getFirstOrganization($user->getOrganizations(true));
        if (null === $firstOrganization) {
            throw new BadUserOrganizationException('The user does not have active organization assigned to it.');
        }

        return $firstOrganization;
    }

    private function getFirstOrganization(Collection $organizations): ?Organization
    {
        return $organizations->first() ?: null;
    }
}
