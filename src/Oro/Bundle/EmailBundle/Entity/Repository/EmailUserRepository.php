<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserRepository extends EntityRepository
{
    /**
     * @param Email        $email
     * @param User         $user
     * @param Organization $organisation
     *
     * @return EmailUser[]
     */
    public function findByEmailAndOwner(Email $email, User $user, Organization $organisation)
    {
        return $this->findBy([
            'email'        => $email,
            'owner'        => $user,
            'organization' => $organisation
        ]);
    }

    /**
     * @param $email Email
     *
     * @return EmailUser[]
     */
    public function findByEmailForMailbox(Email $email)
    {
        return $this->createQueryBuilder('ue')
            ->andWhere('ue.email = :email')
            ->andWhere('ue.mailboxOwner IS NOT NULL')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User         $user
     * @param Organization $organization
     * @param array        $folderTypes
     * @param bool         $isSeen
     *
     * @return array
     */
    public function getEmailUserList(User $user, Organization $organization, array $folderTypes = [], $isSeen = null)
    {
        $qb = $this->createQueryBuilder('eu');
        $qb
            ->join('eu.folders', 'f')
            ->join('f.origin', 'o')
            ->andWhere($qb->expr()->eq('eu.owner', $user->getId()))
            ->andWhere($qb->expr()->eq('eu.organization', $organization->getId()))
            ->andWhere($qb->expr()->eq('o.isActive', ':active'))
            ->setParameter('active', true);

        if ($folderTypes) {
            $qb->andWhere($qb->expr()->in('f.type', $folderTypes));
        }

        if ($isSeen !== null) {
            $qb->andWhere($qb->expr()->eq('eu.seen', ':seen'))
                ->setParameter('seen', (bool)$isSeen);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array       $ids
     * @param EmailFolder $folder
     * @param \DateTime   $date
     *
     * @return array
     */
    public function getInvertedIdsFromFolder(array $ids, EmailFolder $folder, $date = null)
    {
        $qb = $this->createQueryBuilder('email_user');

        $qb->select('email_user.id')
            ->leftJoin('email_user.folders', 'folders')
            ->andWhere($qb->expr()->in('folders', ':folder'))
            ->setParameter('folder', $folder);

        if ($ids) {
            $qb->andWhere($qb->expr()->notIn('email_user.id', ':ids'))
                ->setParameter('ids', $ids);
        }

        if ($date) {
            $qb->andWhere($qb->expr()->gt('email_user.receivedAt', ':date'))
                ->setParameter('date', $date);
        }

        $emailUserIds = $qb->getQuery()->getArrayResult();

        $ids = [];
        foreach ($emailUserIds as $emailUserId) {
            $ids[] = $emailUserId['id'];
        }

        return $ids;
    }

    /**
     * @param array $ids
     * @param bool  $seen
     *
     * @return mixed
     */
    public function setEmailUsersSeen(array $ids, $seen)
    {
        $qb = $this->createQueryBuilder('email_user');

        return $qb->update()->set('email_user.seen', ':seen')
            ->where($qb->expr()->in('email_user.id', ':ids'))
            ->andWhere('email_user.unsyncedFlagCount = 0')
            ->setParameter('seen', $seen)
            ->setParameter('ids', $ids)
            ->getQuery()->execute();
    }

    /**
     * Get all unseen user email
     *
     * @param User $user
     * @param Organization $organization
     * @param array $ids
     * @param array $mailboxIds
     *
     * @return mixed
     */
    public function findUnseenUserEmail(User $user, Organization $organization, $ids = [], $mailboxIds = [])
    {
        $qb = $this->createQueryBuilder('eu');
        $qb->andWhere($qb->expr()->eq('eu.seen', ':seen'))
           ->setParameter('seen', false);

        $uoCheck = call_user_func_array(
            [
                $qb->expr(), 'andX'
            ],
            [
                $qb->expr()->eq('eu.owner', ':owner'),
                $qb->expr()->eq('eu.organization ', ':organization')
            ]
        );

        if (count($mailboxIds)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $uoCheck,
                    $qb->expr()->in('eu.mailboxOwner', ':mailboxIds')
                )
            );
            $qb->setParameter('mailboxIds', $mailboxIds);
        } else {
            $qb->andWhere($uoCheck);
        }

        $qb
            ->setParameter('owner', $user)
            ->setParameter('organization', $organization);

        if (count($ids)) {
            $qb->andWhere($qb->expr()->in('eu.email', ':ids'))
                ->setParameter('ids', $ids);
        }

        return $qb;
    }

    /**
     * @param array                $ids
     * @param User                 $user
     * @param string|string[]|null $folderType
     * @param bool                 $isAllSelected
     * @param Organization         $organization
     *
     * @return QueryBuilder
     */
    public function getEmailUserBuilderForMassAction(
        $ids,
        User $user,
        $folderType,
        $isAllSelected,
        Organization $organization
    ) {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder->join('eu.email', 'e');

        $this->applyOwnerFilter($queryBuilder, $user);
        $this->applyHeadFilter($queryBuilder, true);
        $this->applyOrganizationFilter($queryBuilder, $organization);

        if ($folderType) {
            $this->applyFolderFilter($queryBuilder, $folderType);
        }

        if (!$isAllSelected) {
            $this->applyIdFilter($queryBuilder, $ids);
        } elseif ($ids) {
            $this->applyExcludeIdFilter($queryBuilder, $ids);
        }

        return $queryBuilder;
    }

    /**
     * @param array $threadIds
     * @param User  $user
     *
     * @return QueryBuilder
     */
    public function getEmailUserByThreadId($threadIds, User $user)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder->join('eu.email', 'e');
        $queryBuilder->join('e.thread', 't');
        $this->applyOwnerFilter($queryBuilder, $user);
        $this->applyHeadFilter($queryBuilder, false);
        $queryBuilder->andWhere($queryBuilder->expr()->in('t.id', $threadIds));

        return $queryBuilder;
    }

    /**
     * @param EmailFolder $folder
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return QueryBuilder
     */
    public function getEmailUserByFolder($folder, $limit = null, $offset = null)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder
            ->leftJoin('eu.folders', 'folders')
            ->andWhere($queryBuilder->expr()->in('folders', ':folder'))
            ->setParameter('folder', $folder->getId())
            ->groupBy('eu');
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }

    /**
     * @param EmailFolder $folder
     * @param array       $messages
     *
     * @return EmailUser[]
     */
    public function getEmailUsersByFolderAndMessageIds(EmailFolder $folder, array $messages)
    {
        return $this
            ->createQueryBuilder('eu')
            ->leftJoin('eu.email', 'email')
            ->andWhere('email.messageId IN (:messageIds)')
            ->andWhere('eu.origin IN (:origin)')
            ->setParameter('messageIds', $messages)
            ->setParameter('origin', $folder->getOrigin())
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets array of EmailUser's by Email or EmailThread of this email depends on $checkThread flag
     * for current user and organization or which have mailbox owner.
     *
     * @param Email        $email
     * @param User         $user
     * @param Organization $organization
     * @param bool|false   $checkThread
     *
     * @return EmailUser[]
     */
    public function getAllEmailUsersByEmail(Email $email, User $user, Organization $organization, $checkThread = false)
    {
        $parameters   = [];
        $queryBuilder = $this
            ->createQueryBuilder('eu')
            ->join('eu.email', 'e');

        if ($checkThread && $thread = $email->getThread()) {
            $queryBuilder
                ->andWhere('e.thread = :thread');
            $parameters['thread'] = $thread;
        } else {
            $queryBuilder
                ->andWhere('eu.email = :email');
            $parameters['email'] = $email;
        }

        $orx = $queryBuilder->expr()->orX();
        $orx
            ->add('eu.mailboxOwner IS NOT NULL')
            ->add('eu.owner = :owner AND eu.organization = :organization');

        return $queryBuilder
            ->andWhere($orx)
            ->setParameters(
                array_merge(
                    $parameters,
                    [
                        'owner'        => $user,
                        'organization' => $organization

                    ]
                )
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param User         $user
     *
     * @return $this
     */
    protected function applyOwnerFilter(QueryBuilder $queryBuilder, User $user)
    {
        $queryBuilder->andWhere('eu.owner = ?1');
        $queryBuilder->setParameter(1, $user);

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $ids
     *
     * @return $this
     */
    protected function applyIdFilter(QueryBuilder $queryBuilder, $ids)
    {
        if ($ids) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('eu.id', $ids));
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $ids
     *
     * @return $this
     */
    protected function applyExcludeIdFilter(QueryBuilder $queryBuilder, $ids)
    {
        if ($ids) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('eu.id', $ids));
        }

        return $this;
    }

    /**
     * @param QueryBuilder    $queryBuilder
     * @param string|string[] $type
     *
     * @return $this
     */
    protected function applyFolderFilter(QueryBuilder $queryBuilder, $type)
    {
        $queryBuilder->join('eu.folders', 'f');

        if (!is_array($type)) {
            $type = [$type];
        }

        $expressions = [];
        foreach ($type as $folderType) {
            $expressions[] = $queryBuilder->expr()->eq('f.type', $queryBuilder->expr()->literal($folderType));
        }

        /**
         * In case of "inbox" type we should include "other" type too.
         * Case with selective email folder sync, e.g. when syncing some folder different from "Inbox".
         */
        if (in_array(FolderType::INBOX, $type)) {
            $expressions[] = $queryBuilder->expr()->eq('f.type', $queryBuilder->expr()->literal(FolderType::OTHER));
        }

        $expr = call_user_func_array([$queryBuilder->expr(), 'orX'], $expressions);
        $queryBuilder->andWhere($expr);

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool         $isHead
     */
    protected function applyHeadFilter(QueryBuilder $queryBuilder, $isHead = true)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->andX($queryBuilder->expr()->eq('e.head', ':head')));
        $queryBuilder->setParameter('head', (bool)$isHead);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Organization $organization
     * @return $this
     */
    protected function applyOrganizationFilter(QueryBuilder $queryBuilder, Organization $organization)
    {
        $queryBuilder->andWhere('eu.organization = :organization');
        $queryBuilder->setParameter('organization', $organization);

        return $this;
    }
}
