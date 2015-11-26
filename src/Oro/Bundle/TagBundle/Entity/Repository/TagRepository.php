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
     * @param string $entityClassName
     * @param array  $entityIds
     * @param string $direction
     *
     * @return array [id, name, entityId]
     */
    public function getTagsByEntityIds($entityClassName, array $entityIds, $direction = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id', 't.name', 't2.recordId AS entityId')
            ->innerJoin(
                't.tagging',
                't2',
                Join::WITH,
                't2.recordId IN (:entityIds) AND t2.entityName = :entityClassName'
            )
            ->orderBy('t.name', $direction)
            ->setParameter('entityIds', $entityIds)
            ->setParameter('entityClassName', $entityClassName);

        return $qb->getQuery()->getResult();
    }

    /**
     * returns tags related to entity
     *
     * @param string            $entityClassName
     * @param int               $entityId
     * @param User|null         $owner
     * @param bool|false        $all
     * @param Organization|null $organization
     *
     * @return array
     */
    public function getEntityTags(
        $entityClassName,
        $entityId,
        User $owner = null,
        $all = false,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('t.tagging', 't2', Join::WITH, 't2.recordId = :entityId AND t2.entityName = :entityClassName')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityClassName', $entityClassName);

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
     * @param string    $entityClassName
     * @param int       $entityId
     * @param User|null $owner
     *
     * @return int
     */
    public function deleteEntityTags(array $tagIds, $entityClassName, $entityId, User $owner = null)
    {
        $builder = $this->_em->createQueryBuilder();
        $builder
            ->delete('OroTagBundle:Tagging', 't')
            ->where('t.entityName = :entityClassName')
            ->setParameter('entityClassName', $entityClassName)
            ->andWhere('t.recordId = :entityId')
            ->setParameter('entityId', $entityId);

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
