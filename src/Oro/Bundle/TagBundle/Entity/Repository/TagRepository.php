<?php

namespace Oro\Bundle\TagBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for Tag entity
 */
class TagRepository extends EntityRepository
{
    /**
     * @param string $entityClassName
     * @param array  $entityIds
     * @param User   $owner
     * @param string $direction
     *
     * @return array [id, name, entityId, owner]
     */
    public function getTagsByEntityIds($entityClassName, array $entityIds, User $owner, $direction = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('t')
            ->select(
                't.id',
                't.name',
                'tx.backgroundColor as backgroundColor',
                'tx.name as taxonomyName',
                't2.recordId AS entityId',
                '(CASE WHEN t2.owner = :owner THEN true ELSE false END) AS owner'
            )
            ->innerJoin(
                't.tagging',
                't2',
                Join::WITH,
                't2.recordId IN (:entityIds) AND t2.entityName = :entityClassName'
            )
            ->leftJoin(
                't.taxonomy',
                'tx',
                Join::WITH,
                't.taxonomy  = tx.id'
            )
            ->orderBy('t.name', QueryBuilderUtil::getSortOrder($direction))
            ->setParameter('entityIds', $entityIds)
            ->setParameter('entityClassName', $entityClassName)
            ->setParameter('owner', $owner);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns tags related to entity
     *
     * If $owner is null return all tags for entity.
     * If $owner is not null and $all is true return all tags excluded $owner.
     * If $owner is not null and $all is false return all $owner tags.
     *
     * @param string            $entityClassName
     * @param int               $entityId
     * @param User|null         $owner
     * @param bool|false        $all
     * @param Organization|null $organization
     *
     * @return array
     */
    public function getTags(
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
     * @param Tag[]|int[] $tags
     * @param string      $entityClassName
     * @param int         $entityId
     * @param User|null   $owner
     *
     * @return int
     */
    public function deleteTaggingByParams(array $tags, $entityClassName, $entityId, User $owner = null)
    {
        $builder = $this
            ->getDeleteTaggingQueryBuilder($entityClassName)
            ->andWhere('t.recordId = :entityId')
            ->setParameter('entityId', $entityId);

        if (!empty($tags)) {
            $builder->andWhere($builder->expr()->in('t.tag', ':tags'))->setParameter('tags', $tags);
        }

        if (null !== $owner) {
            $builder->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Deletes tags relations for given entity class.
     *
     * @param string $entityClassName
     *
     * @return int
     */
    public function deleteRelations($entityClassName)
    {
        return $this
            ->getDeleteTaggingQueryBuilder($entityClassName)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $entityClassName
     *
     * @return QueryBuilder
     */
    protected function getDeleteTaggingQueryBuilder($entityClassName)
    {
        return $this->_em->createQueryBuilder()
            ->delete('OroTagBundle:Tagging', 't')
            ->where('t.entityName = :entityClassName')
            ->setParameter('entityClassName', $entityClassName);
    }
}
