<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\PhpUtils\ArrayUtil;

class EmailRepository extends EntityRepository
{
    /**
     * Gets emails by ids
     *
     * @param int[] $ids
     *
     * @return Email[]
     */
    public function findEmailsByIds($ids)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $criteria     = new Criteria();
        $criteria->where(Criteria::expr()->in('id', $ids));
        $criteria->orderBy(['sentAt' => Criteria::DESC]);
        $queryBuilder->addCriteria($criteria);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * Gets email by Message-ID
     *
     * @param string $messageId
     *
     * @return Email|null
     */
    public function findEmailByMessageId($messageId)
    {
        return $this->createQueryBuilder('e')
            ->where('e.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get $limit last emails
     *
     * @param User         $user
     * @param Organization $organization
     * @param int          $limit
     *
     * @return mixed
     */
    public function getNewEmails(User $user, Organization $organization, $limit)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, eu.seen')
            ->leftJoin('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->andWhere('eu.owner = :ownerId')
            ->groupBy('e, eu.seen')
            ->orderBy('e.sentAt', 'DESC')
            ->setParameter('organizationId', $organization->getId())
            ->setParameter('ownerId', $user->getId())
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get emails with empty body and at not synced body state
     *
     * @param int $batchSize
     *
     * @return Email[]
     */
    public function getEmailsWithoutBody($batchSize)
    {
        return $this->createQueryBuilder('email')
            ->select('email')
            ->where('email.emailBody is null')
            ->andWhere('email.bodySynced = false or email.bodySynced is null')
            ->setMaxResults($batchSize)
            ->orderBy('email.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count new emails
     *
     * @param User         $user
     * @param Organization $organization
     *
     * @return mixed
     */
    public function getCountNewEmails(User $user, Organization $organization)
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e)')
            ->leftJoin('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->andWhere('eu.owner = :ownerId')
            ->andWhere('eu.seen = :seen')
            ->setParameter('organizationId', $organization->getId())
            ->setParameter('ownerId', $user->getId())
            ->setParameter('seen', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get email entities by owner entity
     *
     * @param object $entity
     * @param string $ownerColumnName
     *
     * @return array
     */
    public function getEmailsByOwnerEntity($entity, $ownerColumnName)
    {
        return call_user_func_array(
            'array_merge',
            array_map(
                function (QueryBuilder $qb) {
                    return $qb->getQuery()->getResult();
                },
                $this->createEmailsByOwnerEntityQbs($entity, $ownerColumnName)
            )
        );
    }

    /**
     * Has email entities by owner entity
     *
     * @param object $entity
     * @param string $ownerColumnName
     *
     * @return bool
     */
    public function hasEmailsByOwnerEntity($entity, $ownerColumnName)
    {
        return ArrayUtil::some(
            function (QueryBuilder $qb) {
                return (bool) $qb
                    ->select('e.id')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getArrayResult();
            },
            $this->createEmailsByOwnerEntityQbs($entity, $ownerColumnName)
        );
    }

    /**
     * @param object $entity
     * @param string $ownerColumnName
     *
     * @return QueryBuilder[]
     */
    protected function createEmailsByOwnerEntityQbs($entity, $ownerColumnName)
    {
        return [
            $this
                ->createQueryBuilder('e')
                ->join('e.recipients', 'r')
                ->join('r.emailAddress', 'ea')
                ->andWhere(sprintf('ea.%s = :contactId', $ownerColumnName))
                ->andWhere('ea.hasOwner = :hasOwner')
                ->setParameter('contactId', $entity->getId())
                ->setParameter('hasOwner', true),
            $this
                ->createQueryBuilder('e')
                ->join('e.fromEmailAddress', 'ea')
                ->andWhere(sprintf('ea.%s = :contactId', $ownerColumnName))
                ->andWhere('ea.hasOwner = :hasOwner')
                ->setParameter('contactId', $entity->getId())
                ->setParameter('hasOwner', true),
        ];
    }
}
