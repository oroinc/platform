<?php

namespace Oro\Bundle\TagBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\UserBundle\Entity\User;

class TagRepository extends EntityRepository
{
    /**
     * @param string $entityName
     * @param array  $ids
     * @param string $direction
     *
     * @return array
     */
    public function getTagsByEntityIds($entityName, array $ids, $direction = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id', 't.name', 't2.recordId')
            ->innerJoin('t.tagging', 't2', Join::WITH, 't2.recordId IN (:ids) AND t2.entityName = :entityName')
            ->orderBy('t.name', $direction)
            ->setParameter('ids', $ids)
            ->setParameter('entityName', $entityName);

        return $qb->getQuery()->getResult();
    }

    /**
     * returns tags related to entity
     *
     * @param string            $entityName
     * @param int               $entityId
     * @param User|null         $owner
     * @param bool|false        $all
     * @param Organization|null $organization
     *
     * @return array
     */
    public function getEntityTags(
        $entityName,
        $entityId,
        User $owner = null,
        $all = false,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('t.tagging', 't2', Join::WITH, 't2.recordId = :recordId AND t2.entityName = :entityName')
            ->setParameter('recordId', $entityId)
            ->setParameter('entityName', $entityName);

        if (null !== $owner) {
            $qb->where('t2.owner ' . ($all ? '!=' : '=') . ' :owner')
                ->setParameter('owner', $owner);
        }

        if (null !== $organization) {
            $qb->andWhere('t.organization = :organization')
                ->setParameter('organization', $organization);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove tags related to entity
     *
     * @param int[]     $tagIds
     * @param string    $entityName
     * @param int       $recordId
     * @param User|null $owner
     *
     * @return int
     */
    public function deleteEntityTags(array $tagIds, $entityName, $recordId, User $owner = null)
    {
        $builder = $this->_em->createQueryBuilder();
        $builder
            ->delete('OroTagBundle:Tagging', 't')
            ->where('t.entityName = :entityName')
            ->setParameter('entityName', $entityName)
            ->andWhere('t.recordId = :recordId')
            ->setParameter('recordId', $recordId);

        if (!empty($tagIds)) {
            $builder->andWhere($builder->expr()->in('t.tag', $tagIds));
        }

        if (null !== $owner) {
            $builder->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Returns tags with taggings loaded by resource
     *
     * @param object       $resource
     * @param int|null     $createdBy
     * @param bool         $all
     * @param Organization $organization
     *
     * @return array
     *
     * @deprecated Use {@see getEntityTags} instead
     */
    public function getTagging($resource, $createdBy = null, $all = false, Organization $organization = null)
    {
        $recordId = TagManager::getEntityId($resource);

        return $this->getEntityTags(ClassUtils::getClass($resource), $recordId, $createdBy, $all, $organization);
    }

    /**
     * Remove tagging related to tags by params
     *
     * @param array|int $tagIds
     * @param string    $entityName
     * @param int       $recordId
     * @param int|null  $createdBy
     *
     * @return array
     *
     * @deprecated Use {@see deleteEntityTags} instead
     */
    public function deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy = null)
    {
        return $this->deleteEntityTags($tagIds, $entityName, $recordId, $createdBy);
    }
}
