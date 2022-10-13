<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

/**
 * Runs static repository restriction query and stores it state into snapshot entity
 */
class StaticSegmentManager
{
    private ManagerRegistry $doctrine;
    private DynamicSegmentQueryBuilder $dynamicSegmentQueryBuilder;
    private OwnershipMetadataProviderInterface $ownershipMetadataProvider;
    private NativeQueryExecutorHelper $nativeQueryExecutorHelper;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        DynamicSegmentQueryBuilder $dynamicSegmentQueryBuilder,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        NativeQueryExecutorHelper $nativeQueryExecutorHelper,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider
    ) {
        $this->doctrine = $doctrine;
        $this->dynamicSegmentQueryBuilder = $dynamicSegmentQueryBuilder;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
    }

    /**
     * Doctrine does not supports insert in DQL. To increase the speed of query here uses plain sql query.
     */
    public function run(Segment $segment, array $entityIds = []): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Segment::class);
        $connection = $em->getConnection();
        $entityMetadata = $em->getClassMetadata($segment->getEntity());

        if (count($entityMetadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException('Only entities with single identifier supports.');
        }

        $identifier = $entityMetadata->getSingleIdentifierFieldName();
        $qb = $this->getQueryBuilderForSegment($segment, $identifier, $entityIds);
        $selectSql = $this->getSelectSql(clone $qb, $segment, $identifier, count($entityIds) === 0);

        $fieldToSelect = $this->getFieldToSelect($entityMetadata, $identifier);
        [$values, $types] = $this->nativeQueryExecutorHelper->processParameterMappings($qb->getQuery());
        $segmentSnapshotRepository = $em->getRepository(SegmentSnapshot::class);

        $em->beginTransaction();
        try {
            // Remove existing SegmentSnapshot records for given $entityIds
            $segmentSnapshotRepository->removeBySegment($segment, $entityIds);

            // When segment should be partially updated for defined set of entities execute INSERT VALUE
            // to prevent table locking. On full segment rebuild use INSERT FROM SELECT.
            if ($entityIds) {
                $this->executeInsertValues($connection, $fieldToSelect, $selectSql, $values, $types, $segment);
            } else {
                $this->executeInsertFromSelect($connection, $fieldToSelect, $selectSql, $values, $types);
            }

            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();

            throw $exception;
        }

        // Do not update last run on partial segment snapshot update.
        if (!$entityIds) {
            $this->updateSegmentLastRun($em, $segment);
        }
    }

    private function getSelectSql(
        QueryBuilder $queryBuilder,
        Segment $segment,
        string $identifier,
        bool $addSnapshotFields
    ): string {
        // Use Order By to fix deadlock with multi-row INSERT's and 'ON CONFLICT DO NOTHING'.
        if (!$segment->getRecordsLimit()) {
            if ($addSnapshotFields) {
                $this->addSegmentSnapshotFields($queryBuilder, $segment);
            }
            $queryBuilder->addOrderBy(sprintf('%s.%s', $queryBuilder->getRootAliases()[0], $identifier));
            $finalSelectSql = $queryBuilder->getQuery()->getSQL();
        } else {
            $queryBuilder->setMaxResults($segment->getRecordsLimit());
            $originalSelectSql = $queryBuilder->getQuery()->getSQL();

            if ($addSnapshotFields) {
                $this->addSegmentSnapshotFields($queryBuilder, $segment);
            }
            $queryBuilder->resetDQLPart('where');
            $queryBuilder->setMaxResults(null);
            $purifiedSelectSql = $queryBuilder->getQuery()->getSQL();
            $originalIdentifierDoctrineAlias = $this->getDoctrineIdentifierAlias($identifier, $originalSelectSql);

            $finalSelectSql = "$purifiedSelectSql JOIN ($originalSelectSql)"
                . " AS result_table ON result_table.$originalIdentifierDoctrineAlias = $identifier"
                . " ORDER BY $originalIdentifierDoctrineAlias ASC";
        }

        return $finalSelectSql;
    }

    /**
     * Returns doctrine's auto generated identifier alias for column - something like this "id_0"
     */
    private function getDoctrineIdentifierAlias(string $identifier, string $sql): string
    {
        $regex = "/(?<=\b.$identifier AS )(?:[\w\-]+)/is";
        preg_match($regex, $sql, $matches);

        return current($matches);
    }

    private function getFieldToSelect(ClassMetadata $entityMetadata, string $identifier): string
    {
        $fieldToSelect = 'entity_id';
        if ($entityMetadata->getTypeOfField($identifier) === 'integer') {
            $fieldToSelect = 'integer_entity_id';
        }

        return $fieldToSelect;
    }

    private function getQueryBuilderForSegment(
        Segment $segment,
        string $identifier,
        array $entityIds = []
    ): QueryBuilder {
        $queryBuilder = $this->dynamicSegmentQueryBuilder->getQueryBuilder($segment);
        $entityAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->resetDQLPart('select');
        $queryBuilder->select($entityAlias . '.' . $identifier);
        $queryBuilder->resetDQLPart('orderBy');

        $organizationField = $this->ownershipMetadataProvider
            ->getMetadata($segment->getEntity())
            ->getOrganizationFieldName();
        if ($organizationField) {
            $this->organizationRestrictionProvider->applyOrganizationRestrictions(
                $queryBuilder,
                $segment->getOrganization(),
                $entityAlias
            );
        }

        if ($entityIds) {
            $queryBuilder
                ->andWhere($entityAlias . ' IN (:entityIds)')
                ->setParameter('entityIds', $entityIds);
        }

        return $queryBuilder;
    }

    private function addSegmentSnapshotFields(QueryBuilder $queryBuilder, Segment $segment): void
    {
        $queryBuilder->addSelect((string)$queryBuilder->expr()->literal($segment->getId()));
        $queryBuilder->addSelect('CURRENT_TIMESTAMP()');
    }

    private function executeInsertFromSelect(
        Connection $connection,
        string $fieldToSelect,
        string $selectSql,
        array $values,
        array $types
    ): void {
        $dbQuery = "INSERT INTO oro_segment_snapshot ($fieldToSelect, segment_id, createdat) (%s)"
            . ' ON CONFLICT (segment_id, integer_entity_id) DO NOTHING';
        $dbQuery = sprintf($dbQuery, $selectSql);
        $connection->executeQuery($dbQuery, $values, $types);
    }

    private function executeInsertValues(
        Connection $connection,
        string $fieldToSelect,
        string $selectSql,
        array $values,
        array $types,
        Segment $segment
    ): void {
        $stmt = $connection->executeQuery($selectSql, $values, $types);
        $insertValues = [];
        $isIntegerId = $fieldToSelect === 'integer_entity_id';
        while ($row = $stmt->fetch()) {
            $entityId = reset($row);
            $insertValues[] = sprintf(
                '(%s, %d, %s)',
                $isIntegerId ? $entityId : "'{$entityId}'",
                $segment->getId(),
                'NOW()'
            );
        }

        if ($insertValues) {
            $insertValues = implode(',', $insertValues);
            $sql = "INSERT INTO oro_segment_snapshot ($fieldToSelect, segment_id, createdat) VALUES $insertValues"
                . ' ON CONFLICT (segment_id, integer_entity_id) DO NOTHING';
            $connection->executeStatement($sql);
        }
    }

    private function updateSegmentLastRun(EntityManagerInterface $em, Segment $segment): void
    {
        /** @var Segment $segment */
        $segment = $em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $em->persist($segment);
        $em->flush($segment);
    }
}
