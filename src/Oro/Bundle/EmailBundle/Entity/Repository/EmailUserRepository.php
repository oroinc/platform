<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserRepository extends EntityRepository
{
    /**
     * @param Email $email
     * @param User  $user
     *
     * @return null|EmailUser
     */
    public function findByEmailAndOwner(Email $email, User $user)
    {
        return $this->findOneBy([
            'email' => $email,
            'owner' => $user
        ]);
    }

    /**
     * @param User $user
     * @param Organization $organization
     * @param array $folderTypes
     * @param bool $isSeen
     * @return array
     */
    public function getEmailUserList(User $user, Organization $organization, array $folderTypes = [], $isSeen = null)
    {
        $qb = $this->createQueryBuilder('eu');
        $qb
            ->join('eu.folder', 'f')
            ->andWhere($qb->expr()->eq('eu.owner', $user->getId()))
            ->andWhere($qb->expr()->eq('eu.organization', $organization->getId()));

        if ($folderTypes) {
            $qb->andWhere($qb->expr()->in('f.type', $folderTypes));
        }

        if ($isSeen !== null) {
            $qb->andWhere($qb->expr()->eq('eu.seen', $isSeen));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @param User $user
     * @param string $folderType
     * @param bool $isAllSelected
     * @return QueryBuilder
     */
    public function getEmailUserBuilderForMassAction($ids, User $user, $folderType, $isAllSelected)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder->join('eu.email', 'e');

        $this->applyOwnerFilter($queryBuilder, $user);
        $this->applyHeadFilter($queryBuilder, 1);

        if ($folderType) {
            $this->applyFolderFilter($queryBuilder, $folderType);
        }

        if (!$isAllSelected) {
            $this->applyIdFilter($queryBuilder, $ids);
        }

        return $queryBuilder;
    }

    /**
     * @param array $threadIds
     * @param User $user
     * @return QueryBuilder
     */
    public function getEmailUserByThreadId($threadIds, User $user)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder->join('eu.email', 'e');
        $queryBuilder->join('e.thread', 't');
        $this->applyOwnerFilter($queryBuilder, $user);
        $this->applyHeadFilter($queryBuilder, 0);
        $queryBuilder->andWhere($queryBuilder->expr()->in('t.id', $threadIds));

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param User $user
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
     * @param array $ids
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
     * @param string $type
     * @return $this
     */
    protected function applyFolderFilter(QueryBuilder $queryBuilder, $type)
    {
        $queryBuilder->join('eu.folder', 'f');
        $queryBuilder->andWhere($queryBuilder->expr()->eq('f.type', $queryBuilder->expr()->literal($type)));

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $isHead
     */
    protected function applyHeadFilter(QueryBuilder $queryBuilder, $isHead = 1)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->eq('e.head', $isHead));
    }
}
