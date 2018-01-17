<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class UserEmailOriginRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithAccessTokens()
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.accessToken'));

        return $queryBuilder;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithRefreshTokens()
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.refreshToken'));

        return $queryBuilder;
    }

    /**
     * Returns an array with origins by the given array with ids.
     *
     * @param array $originIds
     * @return UserEmailOrigin[]
     */
    public function getOriginsByIds(array $originIds)
    {
        return $this->createQueryBuilder('o')
            ->where('o.id in (:ids)')
            ->setParameter('ids', $originIds)
            ->getQuery()
            ->getResult();
    }
}
