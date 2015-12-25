<?php

namespace Oro\Bundle\NoteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class NoteRepository extends EntityRepository
{
    /**
     * @param string   $entityClassName
     * @param mixed    $entityId
     * @param int|null $page
     * @param int|null $limit
     *
     * @return QueryBuilder
     */
    public function getAssociatedNotesQueryBuilder($entityClassName, $entityId, $page = null, $limit = null)
    {
        $qb = $this->getBaseAssociatedNotesQB($entityClassName, $entityId);
        $qb->select('partial note.{id, message, owner, createdAt, updatedBy, updatedAt}, c, u')
            ->leftJoin('note.owner', 'c')
            ->leftJoin('note.updatedBy', 'u');

        if (null !== $page) {
            $qb->setFirstResult($this->getOffset($page) * $limit);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    /**
     * Get offset by page
     *
     * @param  int|null $page
     * @return int
     */
    protected function getOffset($page)
    {
        if (!$page !== null) {
            $page = $page > 0 ? $page - 1 : 0;
        }

        return $page;
    }

    /**
     * @param $entityClassName
     * @param $entityId
     * @return QueryBuilder
     */
    public function getBaseAssociatedNotesQB($entityClassName, $entityId)
    {
        $ids = is_array($entityId) ? $entityId : [$entityId];
        $queryBuilder = $this->createQueryBuilder('note')
            ->innerJoin(
                $entityClassName,
                'e',
                'WITH',
                sprintf('note.%s = e', ExtendHelper::buildAssociationName($entityClassName))
            );
        $queryBuilder->where($queryBuilder->expr()->in('e.id', $ids));

        return $queryBuilder;
    }
}
