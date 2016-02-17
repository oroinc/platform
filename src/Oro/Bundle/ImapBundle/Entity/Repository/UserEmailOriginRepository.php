<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
}
