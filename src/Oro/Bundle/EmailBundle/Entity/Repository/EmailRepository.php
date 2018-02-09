<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
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
        return $this->findBy(['id' => $ids], ['sentAt' => Criteria::DESC]);
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
        return $this->findOneBy(['messageId' => $messageId]);
    }

    /**
     * Get $limit last emails
     * todo: BAP-11456 Move method getNewEmails from EmailRepository to EmailUserRepository
     *
     * @param User         $user
     * @param Organization $organization
     * @param int          $limit
     * @param int|null     $folderId
     *
     * @return array
     */
    public function getNewEmails(User $user, Organization $organization, $limit, $folderId)
    {
        $qb = $this->getEmailList($user, $organization, $limit, $folderId, false);
        $newEmails = $qb->getQuery()->getResult();
        if (count($newEmails) < $limit) {
            $qb = $this->getEmailList($user, $organization, $limit - count($newEmails), $folderId, true);
            $seenEmails = $qb->getQuery()->getResult();
            $newEmails = array_merge($newEmails, $seenEmails);
        }

        return $newEmails;
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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(DISTINCT IDENTITY(eu.email))')
            ->from('OroEmailBundle:EmailUser', 'eu')
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
     * Get count new emails per folders
     *
     * @param User         $user
     * @param Organization $organization
     * @return array
     */
    public function getCountNewEmailsPerFolders(User $user, Organization $organization)
    {
        $repository = $this->getEntityManager()->getRepository('OroEmailBundle:EmailUser');

        $qb = $repository->createQueryBuilder('eu')
            ->select('COUNT(DISTINCT IDENTITY(eu.email)) num, f.id')
            ->leftJoin('eu.folders', 'f')
            ->where($this->getAclWhereCondition($user, $organization))
            ->andWhere('eu.seen = :seen')
            ->setParameter('organization', $organization)
            ->setParameter('owner', $user)
            ->setParameter('seen', false)
            ->groupBy('f.id');

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
     * Returns QueryBuilder which returns ids or all owner records which have at least one email
     *
     * @param string $ownerClassName
     * @param string $ownerIdentifierName
     * @param string $ownerColumnName
     *
     * @return QueryBuilder
     */
    public function getOwnerIdsWithEmailsQb($ownerClassName, $ownerIdentifierName, $ownerColumnName)
    {
        $qb = $this->_em->createQueryBuilder();

        return $qb
            ->select(QueryBuilderUtil::getField('owner', $ownerIdentifierName))
            ->from($ownerClassName, 'owner')
            ->where($qb->expr()->orX(
                // has incoming email
                $qb->expr()->exists(
                    $this
                        ->createQueryBuilder('e')
                        ->select('e.id')
                        ->join('e.recipients', 'r')
                        ->join('r.emailAddress', 'ea')
                        ->andWhere(
                            $qb->expr()->eq(
                                QueryBuilderUtil::getField('ea', $ownerColumnName),
                                QueryBuilderUtil::getField('owner', $ownerIdentifierName)
                            )
                        )
                        ->andWhere('ea.hasOwner = :hasOwner')
                        ->getDQL()
                ),
                // has outgoing email
                $qb->expr()->exists(
                    $this
                        ->createQueryBuilder('e2')
                        ->select('e2.id')
                        ->join('e2.fromEmailAddress', 'ea2')
                        ->andWhere(
                            $qb->expr()->eq(
                                QueryBuilderUtil::getField('ea2', $ownerColumnName),
                                QueryBuilderUtil::getField('owner', $ownerIdentifierName)
                            )
                        )
                        ->andWhere('ea2.hasOwner = :hasOwner')
                        ->getDQL()
                )
            ))
            ->setParameter('hasOwner', true);
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
                ->andWhere(QueryBuilderUtil::sprintf('ea.%s = :contactId', $ownerColumnName))
                ->andWhere('ea.hasOwner = :hasOwner')
                ->setParameter('contactId', $entity->getId())
                ->setParameter('hasOwner', true),
            $this
                ->createQueryBuilder('e')
                ->join('e.fromEmailAddress', 'ea')
                ->andWhere(QueryBuilderUtil::sprintf('ea.%s = :contactId', $ownerColumnName))
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

    /**
     * @param User         $user
     * @param Organization $organization
     * @param integer      $limit
     * @param integer      $folderId
     * @param bool         $isSeen
     *
     * @return QueryBuilder
     */
    protected function getEmailList(User $user, Organization $organization, $limit, $folderId, $isSeen)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('eu')
            ->from('OroEmailBundle:EmailUser', 'eu')
            ->where($this->getAclWhereCondition($user, $organization))
            ->andWhere('eu.seen = :seen')
            ->orderBy('eu.receivedAt', 'DESC')
            ->setParameter('organization', $organization)
            ->setParameter('owner', $user)
            ->setParameter('seen', $isSeen)
            ->setMaxResults($limit);

        if ($folderId > 0) {
            $qb->leftJoin('eu.folders', 'f')
                ->andWhere('f.id = :folderId')
                ->setParameter('folderId', $folderId);

            return $qb;
        }

        return $qb;
    }
}
