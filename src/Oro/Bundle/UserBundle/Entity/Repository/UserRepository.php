<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class UserRepository extends EntityRepository
{
    /**
     * @param bool|null $enabled
     * @return int
     */
    public function getUsersCount($enabled = null)
    {
        $queryBuilder = $this->createQueryBuilder('user')
            ->select('COUNT(user.id) as usersCount');

        if ($enabled !== null) {
            $queryBuilder->andWhere('user.enabled = :enabled')
                ->setParameter('enabled', $enabled);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOriginOwner(EmailOrigin $origin)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->innerJoin('u.emailOrigins', 'o')
            ->where('o.id = :originId')
            ->setParameter('originId', $origin->getId())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $usernames
     *
     * @return array
     */
    public function findUsersByUsernames(array $usernames)
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u');
        $queryBuilder->where($queryBuilder->expr()->in('u.username', $usernames));

        return $queryBuilder->getQuery()->getResult();
    }
}
