<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Doctrine repository for Oro\Bundle\UserBundle\Entity\User entity.
 */
class UserRepository extends AbstractUserRepository implements EmailAwareRepository
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
                    sprintf(
                        'TRIM(CONCAT(\'"\', %s, \'" <\', CAST(u.email AS string), \'>|\', CAST(o.name AS string)))',
                        $fullNameQueryPart
                    ),
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
                    sprintf(
                        'TRIM(CONCAT(\'"\', %s, \'" <\', CAST(e.email AS string), \'>|\', CAST(o.name AS string)))',
                        $fullNameQueryPart
                    ),
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
        return $this->findBy(['username' => $usernames], ['username' => Criteria::ASC]);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function findUsersByIds(array $ids)
    {
        return $this->findBy(['id' => $ids], ['username' => Criteria::ASC]);
    }

    /**
     * @param string[] $emails
     * @param Organization|null $organization
     *
     * @return User[]
     */
    public function findUsersByEmailsAndOrganization(array $emails, Organization $organization = null)
    {
        if (!$emails) {
            return [];
        }

        $lowerCaseEmails = array_map('strtolower', $emails);

        $queryBuilder = $this->createQueryBuilder('user');

        $queryBuilder
            ->select('user')
            ->leftJoin('user.emails', 'email')
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->in('LOWER(email.email)', ':lowerCaseEmails'),
                $queryBuilder->expr()->in('LOWER(user.email)', ':lowerCaseEmails')
            ))
            ->setParameter('lowerCaseEmails', $lowerCaseEmails);

        if ($organization) {
            $queryBuilder->innerJoin('user.organizations', 'organization')
                ->andWhere('organization = :organization')
                ->setParameter('organization', $organization);
        }

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Return query builder matching enabled users
     *
     * @return QueryBuilder
     */
    public function findEnabledUsersQB()
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('enabled', true);
    }

    /**
     * @return array
     */
    public function findEnabledUserEmails()
    {
        return $this->findEnabledUsersQB()
                ->select('u.id, u.email')
                ->orderBy('u.id')
                ->getQuery()
                ->getArrayResult();
    }

    /**
     * @param array $organizations
     * @return array
     */
    public function findIdsByOrganizations(array $organizations)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.id')
            ->where($qb->expr()->in('u.organization', ':organizations'))
            ->setParameter('organizations', $organizations);

        return array_unique(array_column($qb->getQuery()->getArrayResult(), 'id'));
    }
}
