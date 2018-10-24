<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

/**
 * Runs static repository restriction query and stores it state into snapshot entity
 */
class StaticSegmentManager
{
    /** @var EntityManager */
    protected $em;

    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQB;

    /**
     * @var OwnershipMetadataProviderInterface
     */
    protected $ownershipMetadataProvider;

    /**
     * @param EntityManager                       $em
     * @param DynamicSegmentQueryBuilder          $dynamicSegmentQB
     * @param OwnershipMetadataProviderInterface  $ownershipMetadataProvider
     */
    public function __construct(
        EntityManager $em,
        DynamicSegmentQueryBuilder $dynamicSegmentQB,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->em = $em;
        $this->dynamicSegmentQB = $dynamicSegmentQB;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * Doctrine does not supports insert in DQL. To increase the speed of query here uses plain sql query.
     *
     * @param Segment $segment
     * @param array $entityIds
     * @throws \Exception
     */
    public function run(Segment $segment, array $entityIds = [])
    {
        $entityMetadata = $this->em->getClassMetadata($segment->getEntity());

        if (count($entityMetadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException('Only entities with single identifier supports.');
        }

        $this->em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeBySegment($segment, $entityIds);

        try {
            $this->em->beginTransaction();

            $date       = new \DateTime('now', new \DateTimeZone('UTC'));
            $dateString = '\'' . $date->format('Y-m-d H:i:s') . '\'';

            if ($this->em->getConnection()->getDriver()->getName() === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
                $dateString = sprintf('TIMESTAMP %s', $dateString);
            }

            $insertString = sprintf(
                ', %d, %s ',
                $segment->getId(),
                $dateString
            );

            $qb = $this->dynamicSegmentQB->getQueryBuilder($segment);

            $this->applyOrganizationLimit($segment, $qb);

            $tableAlias = $this->getFromTableAlias($qb);
            $identifier = $entityMetadata->getSingleIdentifierFieldName();

            if ($entityIds) {
                $qb->andWhere($qb->expr()->in($tableAlias . '.' . $identifier, ':entityIds'))
                    ->setParameter('entityIds', $entityIds, Type::TARRAY);
            }

            $originalQuery = $qb->getQuery();

            $selectSql = $this->getSelectSql($qb, $segment, $identifier);
            $selectSql = substr_replace($selectSql, $insertString, stripos($selectSql, 'from'), 0);

            $fieldToSelect = 'entity_id';

            if ($entityMetadata->getTypeOfField($identifier) === 'integer') {
                $fieldToSelect = 'integer_entity_id';
            }

            $dbQuery = 'INSERT INTO oro_segment_snapshot (' . $fieldToSelect . ', segment_id, createdat) (%s)';
            $dbQuery = sprintf($dbQuery, $selectSql);

            $values = [];
            $types = [];
            foreach ($originalQuery->getParameters() as $parameter) {
                /* @var $parameter Parameter */
                $value = $parameter->getValue();
                $type  = $parameter->getType() == Type::TARRAY ? Connection::PARAM_STR_ARRAY : $parameter->getType();
                if (\PDO::PARAM_STR === $type && $value instanceof Segment) {
                    $value = $value->getId();
                }
                $values[] = $value;
                $types[]  = $type;
            }

            $this->em->getConnection()->executeQuery($dbQuery, $values, $types);

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $segment = $this->em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($segment);
        $this->em->flush($segment);
    }

    /**
     * Returns select sql if limit applied wraps it in JOIN
     *
     * @param QueryBuilder $queryBuilder
     * @param Segment $segment
     * @param $identifier
     * @return string
     */
    private function getSelectSql(QueryBuilder $queryBuilder, Segment $segment, $identifier)
    {
        $tableAlias = $this->getFromTableAlias($queryBuilder);

        if (!$segment->getRecordsLimit()) {
            $queryBuilder->resetDQLParts(['orderBy', 'select']);
            $queryBuilder->select($tableAlias . '.' . $identifier);
            $finalSelectSql = $queryBuilder->getQuery()->getSQL();
        } else {
            $queryBuilder->setMaxResults($segment->getRecordsLimit());
            $originalSelectSql = $queryBuilder->getQuery()->getSQL();

            $queryBuilder->resetDQLParts(['orderBy', 'select', 'where']);
            $queryBuilder->setMaxResults(null);
            $queryBuilder->select($tableAlias . '.' . $identifier);
            $purifiedSelectSql = $queryBuilder->getQuery()->getSQL();
            $originalIdentifierDoctrineAlias = $this->getDoctrineIdentifierAlias($identifier, $originalSelectSql);

            $finalSelectSql = "$purifiedSelectSql  JOIN ($originalSelectSql) " .
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
        $regex = "/(?<=\b.$identifier AS )(?:[\w-]+)/is";
        preg_match($regex, $sql, $matches);

        return current($matches);
    }

    /**
     * Limit segment data by segment's organization
     *
     * @param Segment      $segment
     * @param QueryBuilder $qb
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
}
