<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Entity repository for {@see Email} entity.
 */
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
     * Finds messageId of the Email specified by id.
     *
     * @param int $id
     * @return string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findMessageIdByEmailId(int $id): ?string
    {
        $qb = $this->createQueryBuilder('e');

        return $qb
            ->select('e.messageId')
            ->where($qb->expr()->eq('e.id', ':id'))
            ->setParameter('id', $id, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Get $limit last emails
     *
     * @param User         $user
     * @param Organization $organization
     * @param int          $limit
     * @param int|null     $folderId
     * @param AclHelper|null $aclHelper
     *
     * @return array
     */
    public function getNewEmails(User $user, Organization $organization, $limit, $folderId, AclHelper $aclHelper = null)
    {
        $qb = $this->getEmailList($user, $organization, $limit, $folderId, false);
        $query = $qb->getQuery();
        if ($aclHelper) {
            $query = $aclHelper->apply($query);
        }
        $newEmails = $query->getResult();
        if (count($newEmails) < $limit) {
            $qb = $this->getEmailList($user, $organization, $limit - count($newEmails), $folderId, true);
            $query = $qb->getQuery();
            if ($aclHelper) {
                $query = $aclHelper->apply($query);
            }
            $seenEmails = $query->getResult();
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
     * @param AclHelper|null $aclHelper
     *
     * @return mixed
     */
    public function getCountNewEmails(
        User $user,
        Organization $organization,
        $folderId = null,
        AclHelper $aclHelper = null
    ) {
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

        $query = $qb->getQuery();
        if ($aclHelper) {
            $query = $aclHelper->apply($query);
        }

        return $query->getSingleScalarResult();
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

     * @return array [email_id, ...]
     */
    public function getEmailIdsWithoutBody(int $batchSize): array
    {
        $data = $this->createQueryBuilder('email')
            ->select('email.id as id')
            ->where('email.emailBody is null')
            ->andWhere('email.bodySynced = false or email.bodySynced is null')
            ->setMaxResults($batchSize)
            ->orderBy('email.sentAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_column($data, 'id');
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

    public function getEmailUserIdsByEmailAddressQb(string $emailAddress): QueryBuilder
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('eu.id')
            ->distinct()
            ->from(EmailUser::class, 'eu')
            ->join('eu.email', 'e')
            ->join('e.recipients', 'r')
            ->join('r.emailAddress', 'rea')
            ->join('e.fromEmailAddress', 'fea')
            ->where('fea.email = :email OR rea.email = :email')
            ->setParameter('email', $emailAddress);

        return $qb;
    }

    public function isEmailPublic(int $emailId): bool
    {
        $rows = $this->_em->createQueryBuilder()
            ->select('eu')
            ->from(EmailUser::class, 'eu')
            ->where('eu.isEmailPrivate != true')
            ->andWhere('eu.email = :email')
            ->setParameter('email', $emailId)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return !empty($rows);
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
