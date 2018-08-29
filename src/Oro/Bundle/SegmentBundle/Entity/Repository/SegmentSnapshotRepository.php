<?php

namespace Oro\Bundle\SegmentBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for SegmentSnapshot entity.
 */
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
     * @param array $entityIds
     * @return array
     */
    public function removeBySegment(Segment $segment, array $entityIds = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'snp')
            ->where($qb->expr()->eq('snp.segment', ':segment'))
            ->setParameter('segment', $segment);

        if ($entityIds) {
            $entityIdentifierField = $this->getEntityReferenceField($segment);

            $qb->andWhere($qb->expr()->in('snp.' . $entityIdentifierField, ':entityIds'))
                ->setParameter('entityIds', $entityIds);
        }

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
            QueryBuilderUtil::checkIdentifier($key);
            if (\is_array($entity) && array_key_exists('id', $entity)) {
                $entityId  = $entity['id'];
                $className = ClassUtils::getClass($entity['entity']);
                $entityIdentifierField = $this->getEntityReferenceFieldByEntityClass($className);
            } else {
                /** @var object $entity */
                $className = ClassUtils::getClass($entity);
                $metadata  = $entityManager->getClassMetadata($className);
                $entityIds = $metadata->getIdentifierValues($entity);
                $entityId  = reset($entityIds);
                $entityIdentifierField = $this->getEntityReferenceFieldNameByMetadata($metadata);
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
            $deleteParams[$className]['entityIdentifierField'] = $entityIdentifierField;
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
                ->orWhere(
                    $deleteQB->expr()->andX(
                        $deleteQB->expr()->in('snp.segment', ':segmentsIds'),
                        $deleteQB->expr()->in(
                            QueryBuilderUtil::getField('snp', $params['entityIdentifierField']),
                            ':entityIds'
                        )
                    )
                )
                ->setParameter('segmentsIds', $params['segmentIds'])
                ->setParameter('entityIds', $params['entityIds']);
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
        $fieldToSelect = $this->getEntityReferenceField($segment);
        $tableName = QueryBuilderUtil::generateParameterName('snp');
        $paramName = QueryBuilderUtil::generateParameterName('segment');
        $fieldToSelect = sprintf('%s.%s', $tableName, $fieldToSelect);

        $qb = $this->createQueryBuilder($tableName);
        $qb->select($fieldToSelect)
            ->where($qb->expr()->eq($tableName . '.segment', ':' . $paramName))
            ->setParameter($paramName, $segment);

        return $qb;
    }

    /**
     * @param Segment $segment
     * @return string
     */
    private function getEntityReferenceField(Segment $segment)
    {
        return $this->getEntityReferenceFieldByEntityClass($segment->getEntity());
    }

    /**
     * @param string $entityClass
     * @return string
     */
    private function getEntityReferenceFieldByEntityClass($entityClass)
    {
        $entityMetadata = $this->getEntityManager()->getClassMetadata($entityClass);

        return $this->getEntityReferenceFieldNameByMetadata($entityMetadata);
    }

    /**
     * @param ClassMetadata $metadata
     * @return string
     */
    private function getEntityReferenceFieldNameByMetadata(ClassMetadata $metadata)
    {
        $idField = $metadata->getSingleIdentifierFieldName();
        $idFieldType = $metadata->getTypeOfField($idField);

        if ($idFieldType === 'integer') {
            return SegmentSnapshot::ENTITY_REF_INTEGER_FIELD;
        }

        return SegmentSnapshot::ENTITY_REF_FIELD;
    }
}
