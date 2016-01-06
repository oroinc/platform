<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

class SegmentSnapshotRepository extends EntityRepository
{
    const DELETE_BATCH_SIZE = 20;

    /**
     * @param array    $entities
     * @param int|null $batchSize
     * @throws \Exception
     */
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
        $deleteQB = $this->getSnapshotDeleteQueryBuilderByEntities([$entity]);

        return $deleteQB ? $deleteQB->getQuery()->execute() : [];
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

        $deleteParams  = [];
        $entityManager = $this->getEntityManager();

        $segmentQB = $entityManager->createQueryBuilder();
        $segmentQB->select('s.id, s.entity')->from('OroSegmentBundle:Segment', 's');

        foreach ($entities as $key => $entity) {
            if (is_array($entity) && array_key_exists('id', $entity)) {
                $entityId  = $entity['id'];
                $className = ClassUtils::getClass($entity['entity']);
            } else {
                /** @var object $entity */
                $className = ClassUtils::getClass($entity);
                $metadata  = $entityManager->getClassMetadata($className);
                $entityIds = $metadata->getIdentifierValues($entity);
                $entityId  = reset($entityIds);
            }

            if (!$entityId) {
                continue;
            }

            if (!isset($deleteParams[$className])) {
                $segmentQB
                    ->orWhere('s.entity = :className' . $key)
                    ->setParameter('className' . $key, $className);
            }

            $deleteParams[$className]['entityIds'][] = (string)$entityId;
        }

        $segments = $segmentQB->getQuery()->getResult();

        foreach ($segments as $segment) {
            $deleteParams[$segment['entity']]['segmentIds'][] = (string)$segment['id'];
        }

        return $this->getDeleteQueryBuilderByParameters($deleteParams);
    }

    /**
     * @param  array $deleteParams
     * @return QueryBuilder|null
     */
    protected function getDeleteQueryBuilderByParameters($deleteParams)
    {
        $deleteQB = $this->getEntityManager()->createQueryBuilder();
        $deleteQB->delete($this->getEntityName(), 'snp');
        $returnQueryBuilder = false;

        foreach ($deleteParams as $params) {
            if (empty($params['segmentIds']) || empty($params['entityIds'])) {
                continue;
            }

            $deleteQB
                ->orWhere($deleteQB->expr()->andX(
                    $deleteQB->expr()->in('snp.segment', $params['segmentIds']),
                    $deleteQB->expr()->in('snp.entityId', $params['entityIds'])
                ));
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
        $entityMetadata = $this->getEntityManager()->getClassMetadata($segment->getEntity());
        $idField        = $entityMetadata->getSingleIdentifierFieldName();
        $idFieldType    = $entityMetadata->getTypeOfField($idField);
        $fieldToSelect  = SegmentSnapshot::ENTITY_REF_FIELD;
        if ($idFieldType == 'integer') {
            $fieldToSelect = SegmentSnapshot::ENTITY_REF_INTEGER_FIELD;
        }
        $fieldToSelect = sprintf('snp.%s', $fieldToSelect);

        $qb = $this->createQueryBuilder('snp')
            ->select($fieldToSelect)
            ->where('snp.segment = :segment')
            ->setParameter('segment', $segment);

        return $qb;
    }
}
