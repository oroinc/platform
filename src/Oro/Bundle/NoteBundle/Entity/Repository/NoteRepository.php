<?php

namespace Oro\Bundle\NoteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class NoteRepository extends EntityRepository
{
    /**
     * @param string    $entityClassName
     * @param int|int[] $entityId
     * @param int|null  $page
     * @param int|null  $limit
     *
     * @return QueryBuilder
     */
    public function getAssociatedNotesQueryBuilder($entityClassName, $entityId, $page = null, $limit = null)
    {
        $qb = $this->getBaseAssociatedNotesQB($entityClassName, $entityId);
        $qb->select('partial note.{id, message, owner, createdAt, updatedBy, updatedAt}, c, u')
            ->leftJoin('note.owner', 'c')
            ->leftJoin('note.updatedBy', 'u');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult($this->getOffset($page, $limit));
        }

        return $qb;
    }

    /**
     * Get offset by page
     *
     * @param  int|null $page
     * @param  int      $limit
     *
     * @return int
     */
    protected function getOffset($page, $limit)
    {
        $page = (int)$page;
        if ($page > 1) {
            return ($page - 1) * $limit;
        }

        return 0;
    }

    /**
     * @param string    $entityClassName
     * @param int|int[] $entityId
     *
     * @return QueryBuilder
     */
    public function getBaseAssociatedNotesQB($entityClassName, $entityId)
    {
        $ids = is_array($entityId) ? $entityId : [$entityId];
        $relationFieldName = ExtendHelper::buildAssociationName($entityClassName, ActivityScope::ASSOCIATION_KIND);
        $queryBuilder = $this->createQueryBuilder('note')
            ->innerJoin(sprintf('note.%s', $relationFieldName), 'e');
        $queryBuilder->where($queryBuilder->expr()->in('e.id', ':notedEntitiesIds'))
            ->setParameter('notedEntitiesIds', $ids);

        return $queryBuilder;
    }
}
