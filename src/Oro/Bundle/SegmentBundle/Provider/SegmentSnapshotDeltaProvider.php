<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

/**
 * Provider for getting deltas between segment snapshots and real state of segment.
 */
class SegmentSnapshotDeltaProvider
{
    const BATCH_SIZE = 1000;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var DynamicSegmentQueryBuilder
     */
    private $dynamicSegmentQB;

    /**
     * @var array|string[]
     */
    private $classIdentifiers = [];

    /**
     * @var array|string[]
     */
    private $segmentRelationIdentifiers = [];

    /**
     * @param ManagerRegistry $registry
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQB
     */
    public function __construct(ManagerRegistry $registry, DynamicSegmentQueryBuilder $dynamicSegmentQB)
    {
        $this->registry = $registry;
        $this->dynamicSegmentQB = $dynamicSegmentQB;
    }

    /**
     * @param Segment $segment
     * @return \Generator
     */
    public function getAddedEntityIds(Segment $segment)
    {
        $entitySegmentQueryBuilder = $this->dynamicSegmentQB->getQueryBuilder($segment);
        $rootAliases = $entitySegmentQueryBuilder->getRootAliases();
        $rootAlias = reset($rootAliases);
        $identifierField = $rootAlias . '.' . $this->getIdentifierFieldName($segment->getEntity());
        $entitySegmentQueryBuilder->select($identifierField);

        $segmentSnapshotQueryBuilder = $this->getSegmentSnapshotQueryBuilder($segment);
        $entitySegmentQueryBuilder->andWhere(
            $entitySegmentQueryBuilder->expr()->notIn(
                $identifierField,
                $segmentSnapshotQueryBuilder->getDQL()
            )
        );

        foreach ($segmentSnapshotQueryBuilder->getParameters() as $parameter) {
            $entitySegmentQueryBuilder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
            );
        }

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($entitySegmentQueryBuilder));
    }

    /**
     * @param Segment $segment
     * @return \Generator
     */
    public function getRemovedEntityIds(Segment $segment)
    {
        $entitySegmentQueryBuilder = $this->dynamicSegmentQB->getQueryBuilder($segment);
        $rootAliases = $entitySegmentQueryBuilder->getRootAliases();
        $rootAlias = reset($rootAliases);
        $identifierField = $rootAlias . '.' . $this->getIdentifierFieldName($segment->getEntity());
        $entitySegmentQueryBuilder->select($identifierField);

        $segmentSnapshotQueryBuilder = $this->getSegmentSnapshotQueryBuilder($segment);
        $segmentSnapshotQueryBuilder->andWhere(
            $segmentSnapshotQueryBuilder->expr()->notIn(
                'segmentSnapshot.' . $this->getSegmentIdentifierFieldName($segment->getEntity()),
                $entitySegmentQueryBuilder->getDQL()
            )
        );

        foreach ($entitySegmentQueryBuilder->getParameters() as $parameter) {
            $segmentSnapshotQueryBuilder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
            );
        }

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($segmentSnapshotQueryBuilder));
    }

    /**
     * @param Segment $segment
     * @return \Generator
     */
    public function getAllEntityIds(Segment $segment)
    {
        $entitySegmentQueryBuilder = $this->dynamicSegmentQB->getQueryBuilder($segment);
        $rootAliases = $entitySegmentQueryBuilder->getRootAliases();
        $rootAlias = reset($rootAliases);
        $identifierField = $rootAlias . '.' . $this->getIdentifierFieldName($segment->getEntity());
        $entitySegmentQueryBuilder->select($identifierField);

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($entitySegmentQueryBuilder));
    }

    /**
     * @param Segment $segment
     * @return QueryBuilder
     */
    private function getSegmentSnapshotQueryBuilder(Segment $segment)
    {
        $queryBuilder = $this->registry
            ->getRepository(SegmentSnapshot::class)
            ->createQueryBuilder('segmentSnapshot');

        return $queryBuilder
            ->select('segmentSnapshot.' . $this->getSegmentIdentifierFieldName($segment->getEntity()))
            ->where($queryBuilder->expr()->eq('segmentSnapshot.segment', ':segment'))
            ->setParameter('segment', $segment);
    }

    /**
     * @param BufferedIdentityQueryResultIterator $iterator
     * @return \Generator
     */
    private function getResultBatches(BufferedIdentityQueryResultIterator $iterator)
    {
        $index = 0;
        foreach ($iterator as $item) {
            $result[] = $item;
            if (++$index % self::BATCH_SIZE === 0) {
                yield $result;
                $result = [];
            }
        }

        if (!empty($result)) {
            yield $result;
        }
    }

    /**
     * @param string $className
     * @return string
     */
    private function getIdentifierFieldName($className)
    {
        if (empty($this->classIdentifiers[$className])) {
            $this->classIdentifiers[$className] = $this->getClassMetadata($className)->getSingleIdentifierFieldName();
        }

        return $this->classIdentifiers[$className];
    }

    /**
     * @param string $className
     * @return string
     */
    private function getSegmentIdentifierFieldName($className)
    {
        if (empty($this->segmentRelationIdentifiers[$className])) {
            $classMetadata = $this->getClassMetadata($className);
            $identifier = $this->getIdentifierFieldName($className);

            if ($classMetadata->getTypeOfField($identifier) === 'integer') {
                $this->segmentRelationIdentifiers[$className] = SegmentSnapshot::ENTITY_REF_INTEGER_FIELD;
            } else {
                $this->segmentRelationIdentifiers[$className] = SegmentSnapshot::ENTITY_REF_FIELD;
            }
        }

        return $this->segmentRelationIdentifiers[$className];
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    private function getClassMetadata($className)
    {
        return $this->registry->getManagerForClass($className)->getClassMetadata($className);
    }
}
