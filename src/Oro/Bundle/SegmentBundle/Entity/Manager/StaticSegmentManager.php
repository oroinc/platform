<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

class StaticSegmentManager
{
    /** @var EntityManager */
    protected $em;

    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQB;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadataProvider;

    /**
     * @param EntityManager              $em
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQB
     * @param OwnershipMetadataProvider  $ownershipMetadataProvider
     */
    public function __construct(
        EntityManager $em,
        DynamicSegmentQueryBuilder $dynamicSegmentQB,
        OwnershipMetadataProvider $ownershipMetadataProvider
    ) {
        $this->em                        = $em;
        $this->dynamicSegmentQB          = $dynamicSegmentQB;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * Runs static repository restriction query and stores it state into snapshot entity
     * Doctrine does not supports insert in DQL. To increase the speed of query here uses plain sql query.
     *
     * @param Segment $segment
     *
     * @throws \LogicException
     * @throws \Exception
     */
    public function run(Segment $segment)
    {
        if ($segment->getType()->getName() !== SegmentType::TYPE_STATIC) {
            throw new \LogicException('Only static segments could have snapshots.');
        }
        $entityMetadata = $this->em->getClassMetadata($segment->getEntity());

        if (count($entityMetadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException('Only entities with single identifier supports.');
        }

        $this->em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeBySegment($segment);
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
            $originalQuery = $qb->getQuery();
            $identifier = $entityMetadata->getSingleIdentifierFieldName();
            $selectSql = $this->getSelectSql($qb, $segment, $identifier);
            $selectSql = substr_replace($selectSql, $insertString, stripos($selectSql, 'from'), 0);

            $fieldToSelect = 'entity_id';

            if ($entityMetadata->getTypeOfField($identifier) === 'integer') {
                $fieldToSelect = 'integer_entity_id';
            }

            $dbQuery = 'INSERT INTO oro_segment_snapshot (' . $fieldToSelect . ', segment_id, createdat) (%s)';
            $dbQuery = sprintf($dbQuery, $selectSql);

            $statement = $this->em->getConnection()->prepare($dbQuery);
            $this->bindParameters($statement, $originalQuery->getParameters());
            $statement->execute();

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $segment = $this->em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($segment);
        $this->em->flush();
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
        $tableAlias = current($queryBuilder->getDQLPart('from'))->getAlias();

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
            ->getGlobalOwnerFieldName();
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

    /**
     * Bind parameters to statement
     *
     * @param Statement       $stmt
     * @param ArrayCollection $parameters
     */
    public function bindParameters(Statement $stmt, ArrayCollection $parameters)
    {
        $values = [];
        $types  = [];
        foreach ($parameters as $parameter) {
            /* @var $parameter Parameter */
            $values[] = $parameter->getValue();
            $types[]  = $parameter->getType();
        }
        $typeOffset = array_key_exists(0, $types) ? -1 : 0;
        $bindIndex  = 1;

        foreach ($values as $value) {
            $typeIndex = $bindIndex + $typeOffset;
            if (isset($types[$typeIndex])) {
                $type = $types[$typeIndex];
                $stmt->bindValue($bindIndex, $value, $type);
            } else {
                $stmt->bindValue($bindIndex, $value);
            }
            ++$bindIndex;
        }
    }
}
