<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class UserApiRepository extends EntityRepository
{
    /**
     * Get UserApi by user and organization
     *
     * @param User         $user
     * @param Organization $organization
     *
     * @return Organization
     */
    public function getApi(User $user, Organization $organization)
    {
        return $this->createQueryBuilder('api')
            ->select('api')
            ->where('api.user = :user')
            ->andWhere('api.organization = :org')
            ->setParameter('user', $user)
            ->setParameter('org', $organization)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
