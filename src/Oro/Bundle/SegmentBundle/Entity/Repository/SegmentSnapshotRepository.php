<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentSnapshotRepository extends EntityRepository
{
    const DELETE_BATCH_SIZE = 20;

    public function massRemoveByEntities($entities, $batchSize = null)
    {
        $batchSize     = $batchSize ? $batchSize : self::DELETE_BATCH_SIZE;
        $entityBatches = array_chunk($entities, $batchSize);
        $entityManager = $this->getEntityManager();

        $entityManager->beginTransaction();
        try {
            foreach ($entityBatches as $entityBatch) {
                $deleteQB = $this->getSnapshotDeleteQueryBuilderByEntities($entityBatch);
                $deleteQB->getQuery()->getResult();
            }
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

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
        $deleteQB = $this->getSnapshotDeleteQueryBuilderByEntities(array($entity));

        return $deleteQB->getQuery()->getResult();
    }

    /**
     * Returns DELETE query builder with conditions for deleting from snapshot table by entity
     *
     * @param array $entities
     * @return QueryBuilder
     */
    protected function getSnapshotDeleteQueryBuilderByEntities(array $entities)
    {
        $entityManager = $this->getEntityManager();
        $deleteQB = $entityManager->createQueryBuilder();
        $deleteQB->delete($this->getEntityName(), 'snp');

        foreach ($entities as $key => $entity) {
            $className   = ClassUtils::getClass($entity);
            $metadata    = $entityManager->getClassMetadata($className);
            $identifiers = $metadata->getIdentifier();
            $tableAlias  = 's' . $key;

            if (!empty($identifiers)) {
                $entityId  = $metadata->getFieldValue($entity, reset($identifiers));
                $segmentQB = $entityManager->createQueryBuilder();

                $segmentQB->select($tableAlias . '.id')
                    ->from('OroSegmentBundle:Segment', $tableAlias)
                    ->where($tableAlias . '.entity = :entityName' . $key);

                $deleteQB
                    ->orWhere('snp.segment IN (' . $segmentQB->getDQL() . ') AND snp.entityId = :entityId')
                    ->setParameter('entityName' . $key, $className)
                    ->setParameter('entityId', $entityId);
            }
        }

        return $deleteQB;
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
