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

                if ($deleteQB) {
                    $deleteQB->getQuery()->execute();
                }
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

        return $qb->getQuery()->execute();
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

        return $deleteQB ? $deleteQB->getQuery()->execute() : array();
    }

    /**
     * Returns DELETE query builder with conditions for deleting from snapshot table by entity
     *
     * @param array $entities
     * @throws \InvalidArgumentException
     * @return QueryBuilder|null
     */
    protected function getSnapshotDeleteQueryBuilderByEntities(array $entities)
    {
        if (empty($entities)) {
            throw new \InvalidArgumentException('List of entity can not be empty');
        }

        $deleteParams  = array();
        $entityManager = $this->getEntityManager();

        $segmentQB = $entityManager->createQueryBuilder();
        $segmentQB->select('s.id, s.entity')->from('OroSegmentBundle:Segment', 's');

        foreach ($entities as $key => $entity) {
            $className = ClassUtils::getClass($entity);
            $metadata  = $entityManager->getClassMetadata($className);
            $entityIds = $metadata->getIdentifierValues($entity);

            if (!isset($deleteParams[$className])) {
                $segmentQB
                    ->orWhere('s.entity = :className' . $key)
                    ->setParameter('className' . $key, $className);
            }

            $deleteParams[$className]['entityIds'][] = reset($entityIds);
        }

        $segments = $segmentQB->getQuery()->getResult();

        $deleteQB = $entityManager->createQueryBuilder();
        $deleteQB->delete($this->getEntityName(), 'snp');

        foreach ($segments as $segment) {
            $deleteParams[$segment['entity']]['segmentIds'][] = $segment['id'];
        }

        $returnQueryBuilder = false;

        foreach ($deleteParams as $params) {
            if (empty($params['segmentIds'])) {
                continue;
            }

            $suffix = implode('_', $params['entityIds']);
            $deleteQB
                ->orWhere($deleteQB->expr()->andX(
                    $deleteQB->expr()->in('snp.segment', ':segmentIds' . $suffix),
                    $deleteQB->expr()->in('snp.entityId', ':entityIds' . $suffix)
                ))
                ->setParameter('segmentIds' . $suffix, implode(',', $params['segmentIds']))
                ->setParameter('entityIds' . $suffix, $params['entityIds']);
            $returnQueryBuilder = true;
        }

        return $returnQueryBuilder ? $deleteQB : null;
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
