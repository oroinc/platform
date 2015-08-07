<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;

class UserRepository extends EntityRepository implements EmailAwareRepository
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
     * @param string $fullNameQueryPart
     * @param array $excludedEmails
     * @param string|null $query
     *
     * @return QueryBuilder
     */
    public function getPrimaryEmailsQb($fullNameQueryPart, array $excludedEmails = [], $query = null)
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->select(sprintf('%s AS name', $fullNameQueryPart))
            ->addSelect('u.id AS entityId, u.email, o.name AS organization')
            ->orderBy('name')
            ->leftJoin('u.organization', 'o');

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like($fullNameQueryPart, ':query'),
                    $qb->expr()->like('u.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($excludedEmails) {
            $qb
                ->andWhere($qb->expr()->notIn('u.email', ':excluded_emails'))
                ->setParameter('excluded_emails', $excludedEmails);
        }

        return $qb;
    }

    /**
     * @param string $fullNameQueryPart
     * @param array $excludedEmails
     * @param string|null $query
     *
     * @return QueryBuilder
     */
    public function getSecondaryEmailsQb($fullNameQueryPart, array $excludedEmails = [], $query = null)
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->select(sprintf('%s AS name', $fullNameQueryPart))
            ->addSelect('e.email')
            ->addSelect('u.id AS entityId, e.email, o.name AS organization')
            ->orderBy('name')
            ->join('u.emails', 'e')
            ->leftJoin('u.organization', 'o');

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like($fullNameQueryPart, ':query'),
                    $qb->expr()->like('e.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($excludedEmails) {
            $qb
                ->andWhere($qb->expr()->notIn('e.email', ':excluded_emails'))
                ->setParameter('excluded_emails', $excludedEmails);
        }

        return $qb;
    }
}
