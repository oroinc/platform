<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\UserBundle\Entity\User;

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
     * {@inheritdoc}
     */
    public function getPrimaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null)
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

        if ($excludedEmailNames) {
            $qb
                ->andWhere($qb->expr()->notIn(
                    sprintf('TRIM(CONCAT(%s, \' <\', u.email, \'>|\', o.name))', $fullNameQueryPart),
                    ':excluded_emails'
                ))
                ->setParameter('excluded_emails', array_values($excludedEmailNames));
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecondaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null)
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

        if ($excludedEmailNames) {
            $qb
                ->andWhere($qb->expr()->notIn(
                    sprintf('TRIM(CONCAT(%s, \' <\', e.email, \'>|\', o.name))', $fullNameQueryPart),
                    ':excluded_emails'
                ))
                ->setParameter('excluded_emails', array_values($excludedEmailNames));
        }

        return $qb;
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
        $queryBuilder->where($queryBuilder->expr()->in('u.username', $usernames))
            ->orderBy('u.username');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function findUsersByIds(array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u');
        $queryBuilder->where($queryBuilder->expr()->in('u.id', $ids))
            ->orderBy('u.username');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string[] $emails
     *
     * @return User[]
     */
    public function findUsersByEmails(array $emails)
    {
        if (!$emails) {
            return [];
        }

        $lowerEmails = array_map('strtolower', $emails);

        $qb = $this->createQueryBuilder('u');

        return $qb
            ->select('u')
            ->leftJoin('u.emails', 'e')
            ->where($qb->expr()->orX(
                $qb->expr()->in('LOWER(e.email)', $lowerEmails),
                $qb->expr()->in('LOWER(u.email)', $lowerEmails)
            ))
            ->getQuery()
            ->getResult();
    }

    /**
     * Sets password expiry date of ALL users
     *
     * @param \DateTime|null $value
     */
    public function updateAllUsersPasswordExpiration(\DateTime $value = null)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->update()
            ->set('u.passwordExpiresAt', ':expiryDate')
            ->setParameter('expiryDate', $value, Type::DATETIME);

        $qb->getQuery()->execute();
    }

    /**
     * Return query builder matching users which their passwords are about to expire in specified list of days
     *
     * @param int[] $days
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getExpiringPasswordUsersQueryBuilder(array $days)
    {
        if (0 === count($days)) {
            throw new \InvalidArgumentException('At least one notification period should be provided');
        }

        $utc = new \DateTimeZone('UTC');
        $builder = $this->createQueryBuilder('u');
        $conditions = [];

        foreach ($days as $index => $day) {
            $from = new \DateTime('+' . $day . ' day midnight', $utc);
            $to = new \DateTime('+' .  $day . ' day 23:59:59', $utc);
            $conditions[] = $builder->expr()->between(
                'u.passwordExpiresAt',
                ':from' . $index,
                ':to' . $index
            );
            $builder->setParameter('from' . $index, $from, Type::DATETIME);
            $builder->setParameter('to' . $index, $to, Type::DATETIME);
        }

        return $builder
            ->select('u')
            ->andWhere('u.enabled = :enabled')
            ->andWhere($builder->expr()->orX()->addMultiple($conditions))
            ->setParameter('enabled', true);
    }

    /**
     * Get array of enabled users with expired passwords
     *
     * @return array
     */
    public function getExpiredPasswordUserIds()
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.id')
            ->andWhere('u.passwordExpiresAt <= :expiresAt')
            ->andWhere('u.loginDisabled = :loginDisabled')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('expiresAt', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME)
            ->setParameter('loginDisabled', false)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'id');
    }
}
