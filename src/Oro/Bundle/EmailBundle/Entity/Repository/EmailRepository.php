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
     * @param int|null     $folderId
     *
     * @return mixed
     */
    public function getNewEmails(User $user, Organization $organization, $limit, $folderId)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, eu.seen')
            ->leftJoin('e.emailUsers', 'eu')
            ->where($this->getAclWhereCondition($user, $organization))
            ->groupBy('e, eu.seen')
            ->orderBy('eu.seen', 'ASC')
            ->addOrderBy('e.sentAt', 'DESC')
            ->setParameter('organization', $organization)
            ->setParameter('owner', $user)
            ->setMaxResults($limit);

        if ($folderId > 0) {
            $qb->leftJoin('eu.folders', 'f')
               ->andWhere('f.id = :folderId')
               ->setParameter('folderId', $folderId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get count new emails
     *
     * @param User         $user
     * @param Organization $organization
     * @param int|null     $folderId
     *
     * @return mixed
     */
    public function getCountNewEmails(User $user, Organization $organization, $folderId = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e)')
            ->leftJoin('e.emailUsers', 'eu')
            ->where($this->getAclWhereCondition($user, $organization))
            ->andWhere('eu.seen = :seen')
            ->setParameter('organization', $organization)
            ->setParameter('owner', $user)
            ->setParameter('seen', false);

        if ($folderId !== null && $folderId > 0) {
            $qb->leftJoin('eu.folders', 'f')
                ->andWhere('f.id = :folderId')
                ->setParameter('folderId', $folderId);
        }

        return $qb->getQuery()->getSingleScalarResult();
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
     * Get count new emails per folders
     *
     * @param User         $user
     * @param Organization $organization
     * @return array
     */
    public function getCountNewEmailsPerFolders(User $user, Organization $organization)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e) num, f.id')
            ->leftJoin('e.emailUsers', 'eu')
            ->where($this->getAclWhereCondition($user, $organization))
            ->andWhere('eu.seen = :seen')
            ->setParameter('organization', $organization)
            ->setParameter('owner', $user)
            ->setParameter('seen', false)
            ->leftJoin('eu.folders', 'f')
            ->groupBy('f.id');

        return $qb->getQuery()->getResult();
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
    public function createEmailsByOwnerEntityQbs($entity, $ownerColumnName)
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

    /**
     * @param User         $user
     * @param Organization $organization
     *
     * @return \Doctrine\ORM\Query\Expr\Orx
     */
    protected function getAclWhereCondition(User $user, Organization $organization)
    {
        $mailboxes = $this->getEntityManager()->getRepository('OroEmailBundle:Mailbox')
            ->findAvailableMailboxIds($user, $organization);

        $expr = $this->getEntityManager()->createQueryBuilder()->expr();

        $andExpr = $expr->andX(
            'eu.owner = :owner',
            'eu.organization = :organization'
        );

        if ($mailboxes) {
            return $expr->orX(
                $andExpr,
                $expr->in('eu.mailboxOwner', $mailboxes)
            );
        } else {
            return $andExpr;
        }
    }
}
