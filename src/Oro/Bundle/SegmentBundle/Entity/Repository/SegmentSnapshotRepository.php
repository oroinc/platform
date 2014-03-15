<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentSnapshotRepository extends EntityRepository
{
    /**
     * @param Segment $segment
     *
     * @return array
     */
    public function removeBySegment(Segment $segment)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'snp')
            ->where('snp.segment = :segment')
            ->setParameter('segment', $segment);

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove snapshot items by entity
     * Handle snapshot removals
     *
     * @param Object $entity
     *
     * @return array
     */
    public function removeByEntity($entity)
    {
        $segmentQB = $this->getEntityManager()->createQueryBuilder();
        $segmentQB->select('s.id')
            ->from('OroSegmentBundle:Segment', 's')
            ->where('s.entity = :entityName');

        $className = ClassUtils::getClass($entity);
        $ids       = $this->getEntityManager()->getClassMetadata($className)->getIdentifierValues($entity);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'snp')
            ->andWhere('snp.entityId = :entityId')
            ->andWhere($qb->expr()->in('snp.segment', $segmentQB->getDQL()))
            ->setParameter('entityName', $className)
            ->setParameter('entityId', implode('', $ids));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get SELECT query builder for retrieving entity identifiers from snapshot
     *
     * @param Segment $segment
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getIdentifiersSelectQueryBuilder(Segment $segment)
    {
        $qb = $this->createQueryBuilder('snp')
            ->select('snp.entityId')
            ->where('snp.segment = :segment')
            ->setParameter('segment', $segment);

        return $qb;
    }
}
