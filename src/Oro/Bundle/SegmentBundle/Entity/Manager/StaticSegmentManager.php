<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

/**
 * Runs static repository restriction query and stores it state into snapshot entity
 */
class StaticSegmentManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DynamicSegmentQueryBuilder
     */
    protected $dynamicSegmentQB;

    /**
     * @var OwnershipMetadataProviderInterface
     */
    protected $ownershipMetadataProvider;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $nativeQueryExecutorHelper;

    public function __construct(
        EntityManager $em,
        DynamicSegmentQueryBuilder $dynamicSegmentQB,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        NativeQueryExecutorHelper $nativeQueryExecutorHelper
    ) {
        $this->em = $em;
        $this->dynamicSegmentQB = $dynamicSegmentQB;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
    }

    /**
     * Doctrine does not supports insert in DQL. To increase the speed of query here uses plain sql query.
     *
     * @throws \Exception
     */
    public function run(Segment $segment, array $entityIds = [])
    {
        $entityMetadata = $this->em->getClassMetadata($segment->getEntity());

        if (count($entityMetadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException('Only entities with single identifier supports.');
        }

        $identifier = $entityMetadata->getSingleIdentifierFieldName();
        $qb = $this->getQueryBuilderForSegment($segment, $identifier, $entityIds);
        $selectSql = $this->getSelectSql(clone $qb, $segment, $identifier, count($entityIds) === 0);

        $fieldToSelect = $this->getFieldToSelect($entityMetadata, $identifier);
        [$values, $types] = $this->nativeQueryExecutorHelper->processParameterMappings($qb->getQuery());
        try {
            $this->em->beginTransaction();
            // Remove existing SegmentSnapshot records for given $entityIds
            $this->em->getRepository(SegmentSnapshot::class)->removeBySegment($segment, $entityIds);

            // When segment should be partially updated for defined set of entities execute INSERT VALUE
            // to prevent table locking. On full segment rebuild use INSERT FROM SELECT.
            if ($entityIds) {
                $this->executeInsertValues($fieldToSelect, $selectSql, $values, $types, $segment);
            } else {
                $this->executeInsertFromSelect($fieldToSelect, $selectSql, $values, $types);
            }

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        // Do not update last run on partial segment snapshot update.
        if (!$entityIds) {
            $this->updateSegmentLastRun($segment);
        }
    }

    /**
     * Returns select sql if limit applied wraps it in JOIN
     *
     * @param QueryBuilder $queryBuilder
     * @param Segment $segment
     * @param string $identifier
     * @param bool $addSnapshotFields
     * @return string
     */
    private function getSelectSql(
        QueryBuilder $queryBuilder,
        Segment $segment,
        $identifier,
        $addSnapshotFields = true
    ): string {
        if (!$segment->getRecordsLimit()) {
            if ($addSnapshotFields) {
                $this->addSegmentSnapshotFields($queryBuilder, $segment);
            }
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

            $finalSelectSql = "$purifiedSelectSql JOIN ($originalSelectSql) " .
                "AS result_table ON result_table.$originalIdentifierDoctrineAlias=$identifier";
        }

        return $finalSelectSql;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    private function getFromTableAlias(QueryBuilder $queryBuilder)
    {
        return current($queryBuilder->getDQLPart('from'))->getAlias();
    }

    /**
     * Returns doctrine's auto generated identifier alias for column - something like this "id_0"
     * @param string $identifier
     * @param string $sql
     * @return string
     */
    private function getDoctrineIdentifierAlias($identifier, $sql)
    {
        $regex = "/(?<=\b.$identifier AS )(?:[\w\-]+)/is";
        preg_match($regex, $sql, $matches);

        return current($matches);
    }

    /**
     * Limit segment data by segment's organization
     */
    protected function applyOrganizationLimit(Segment $segment, QueryBuilder $qb)
    {
        $organizationField = $this->ownershipMetadataProvider
            ->getMetadata($segment->getEntity())
            ->getOrganizationFieldName();
        if ($organizationField) {
            $qb->andWhere(
                sprintf(
                    '%s.%s = %s',
                    $qb->getRootAliases()[0],
                    $organizationField,
                    $segment->getOrganization()->getId()
                )
            );
        }
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
        $queryBuilder = $this->dynamicSegmentQB->getQueryBuilder($segment);
        $this->applyOrganizationLimit($segment, $queryBuilder);
        $tableAlias = $this->getFromTableAlias($queryBuilder);

        if ($entityIds) {
            $queryBuilder->andWhere($queryBuilder->expr()->in($tableAlias, ':entityIds'))
                ->setParameter('entityIds', $entityIds);
        }

        $queryBuilder->resetDQLPart('orderBy');
        $queryBuilder->resetDQLPart('select');
        $queryBuilder->select($tableAlias . '.' . $identifier);

        return $queryBuilder;
    }

    private function addSegmentSnapshotFields(QueryBuilder $queryBuilder, Segment $segment): void
    {
        $queryBuilder->addSelect((string)$queryBuilder->expr()->literal($segment->getId()));
        $queryBuilder->addSelect('CURRENT_TIMESTAMP()');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeInsertFromSelect(
        string $fieldToSelect,
        string $selectSql,
        array $values,
        array $types
    ) {
        $dbQuery = 'INSERT INTO oro_segment_snapshot (' . $fieldToSelect . ', segment_id, createdat) (%s)';
        $dbQuery = sprintf($dbQuery, $selectSql);

        $this->em->getConnection()->executeQuery($dbQuery, $values, $types);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeInsertValues(
        string $fieldToSelect,
        string $selectSql,
        array $values,
        array $types,
        Segment $segment
    ) {
        $stmt = $this->em->getConnection()->executeQuery($selectSql, $values, $types);
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
            $this->em->getConnection()->executeStatement(
                'INSERT INTO oro_segment_snapshot (' . $fieldToSelect . ', segment_id, createdat) VALUES'
                . implode(',', $insertValues)
            );
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateSegmentLastRun(Segment $segment): void
    {
        /** @var Segment $segment */
        $segment = $this->em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($segment);
        $this->em->flush($segment);
    }
}
