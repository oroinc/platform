<?php

namespace Oro\Bundle\NoteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\Model\EntityIdSoap;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class NoteRepository extends EntityRepository
{
    /**
     * @param EntityIdSoap $entityId
     * @param null         $page
     * @param null         $limit
     * @return array
     */
    public function findByAssociatedEntity(EntityIdSoap $entityId, $page = null, $limit = null)
    {
        $entityClass = $entityId->getEntity();

        $qb = $this->createQueryBuilder('note')
            ->select('partial note.{id, message, owner, createdAt, updatedBy, updatedAt}')
            ->innerJoin(
                $entityClass,
                'e',
                'WITH',
                sprintf('note.%s = e', ExtendHelper::buildAssociationName($entityClass))
            )
            ->where('e.id = :entity_id')
            ->orderBy('note.updatedBy', 'DESC')
            ->setParameter('entity_id', $entityId->getId());

        if (false === is_null($page)) {
            $qb->setFirstResult($this->getOffset($page) * $limit);
        }

        if (false === is_null($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
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
} 
