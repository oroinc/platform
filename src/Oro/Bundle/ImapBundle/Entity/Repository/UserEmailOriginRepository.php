<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserEmailOriginRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param Organization $organization
     *
     * @return array
     */
    public function findUserEmailOrigin(User $user, Organization $organization)
    {
        $origin = $this->createQueryBuilder('origin')
            ->andWhere('origin.owner = :owner AND origin.organization = :organization')
            ->andWhere('origin.isActive = 1')
            ->setParameter('owner', $user)
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();

        return $origin;
    }
}
