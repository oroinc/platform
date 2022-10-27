<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

/**
 * Provider for getting deltas between segment snapshots and real state of segment.
 */
class SegmentSnapshotDeltaProvider
{
    private const BATCH_SIZE = 1000;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var DynamicSegmentQueryBuilder */
    private $segmentQueryBuilder;

    /** @var string[] */
    private $classIdentifiers = [];

    /** @var string[] */
    private $segmentRelationIdentifiers = [];

    public function __construct(ManagerRegistry $doctrine, DynamicSegmentQueryBuilder $segmentQueryBuilder)
    {
        $this->doctrine = $doctrine;
        $this->segmentQueryBuilder = $segmentQueryBuilder;
    }

    public function getAddedEntityIds(Segment $segment): iterable
    {
        $entitySegmentQueryBuilder = $this->segmentQueryBuilder->getQueryBuilder($segment);
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
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($entitySegmentQueryBuilder));
    }

    public function getRemovedEntityIds(Segment $segment): iterable
    {
        $entitySegmentQueryBuilder = $this->segmentQueryBuilder->getQueryBuilder($segment);
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
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($segmentSnapshotQueryBuilder));
    }

    public function getAllEntityIds(Segment $segment): iterable
    {
        $entitySegmentQueryBuilder = $this->segmentQueryBuilder->getQueryBuilder($segment);
        $rootAliases = $entitySegmentQueryBuilder->getRootAliases();
        $rootAlias = reset($rootAliases);
        $identifierField = $rootAlias . '.' . $this->getIdentifierFieldName($segment->getEntity());
        $entitySegmentQueryBuilder->select($identifierField);

        return $this->getResultBatches(new BufferedIdentityQueryResultIterator($entitySegmentQueryBuilder));
    }

    private function getSegmentSnapshotQueryBuilder(Segment $segment): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager(SegmentSnapshot::class)->createQueryBuilder();

        return $queryBuilder
            ->from(SegmentSnapshot::class, 'segmentSnapshot')
            ->select('segmentSnapshot.' . $this->getSegmentIdentifierFieldName($segment->getEntity()))
            ->where($queryBuilder->expr()->eq('segmentSnapshot.segment', ':segment'))
            ->setParameter('segment', $segment);
    }

    private function getResultBatches(BufferedIdentityQueryResultIterator $iterator): iterable
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

    private function getIdentifierFieldName(string $className): string
    {
        if (empty($this->classIdentifiers[$className])) {
            $this->classIdentifiers[$className] = $this->getClassMetadata($className)->getSingleIdentifierFieldName();
        }

        return $this->classIdentifiers[$className];
    }

    private function getSegmentIdentifierFieldName(string $className): string
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

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->getEntityManager($entityClass)->getClassMetadata($entityClass);
    }
}
