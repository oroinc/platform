<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Select;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class PostgresqlGridModifier extends AbstractExtension
{
    const PRIORITY = -261;

    /** @var string */
    protected $databaseDriver;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param string $databaseDriver
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct($databaseDriver, EntityClassResolver $entityClassResolver)
    {
        $this->databaseDriver = $databaseDriver;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $this->databaseDriver === DatabaseDriverInterface::DRIVER_POSTGRESQL;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }

    /**
     * Add sorting by identifier because postgresql return rows in different order on two the same sql, but
     * different LIMIT number
     *
     * @param DatagridConfiguration $config
     * @param DatasourceInterface $datasource
     * @return mixed|void
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        //getQueryBuilder exists only in datagrid orm datasource
        if (!$datasource instanceof OrmDatasource) {
            return;
        }

        $entityClassName = $this->getEntityClassName($config);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $datasource->getQueryBuilder();

        if (!$entityClassName) {
            return;
        }

        $fromParts = $queryBuilder->getDQLPart('from');
        $alias = false;

        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($entityClassName);
        $identifier = $metadata->getSingleIdentifierFieldName();

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart->getFrom()) == $entityClassName) {
                $alias = $fromPart->getAlias();
                break;
            }
        }

        if ($alias && $this->isAllowedAddingSorting($alias, $identifier, $queryBuilder)) {
            $field = $alias . '.' . $identifier;
            $orderBy = $queryBuilder->getDQLPart('orderBy');
            if (!isset($orderBy[$field])) {
                if ($this->isDistinct($queryBuilder)) {
                    $this->ensureIdentifierSelected($queryBuilder, $field);
                }
                $queryBuilder->addOrderBy($field, 'ASC');
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    protected function getEntityClassName(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath('[extended_entity_name]');
        if ($entityClassName) {
            return $entityClassName;
        }

        $from = $config->offsetGetByPath('[source][query][from]');
        if (count($from) !== 0) {
            return $this->entityClassResolver->getEntityClass($from[0]['table']);
        }

        return null;
    }

    /**
     * @param string $alias
     * @param string $identifier
     * @param QueryBuilder $queryBuilder
     * @return bool
     */
    protected function isAllowedAddingSorting($alias, $identifier, QueryBuilder $queryBuilder)
    {
        $groupByParts = $queryBuilder->getDQLPart('groupBy');

        if (!count($groupByParts)) {
            return true;
        }

        foreach ($groupByParts as $groupBy) {
            if (in_array($alias.'.'.$identifier, $groupBy->getParts(), true) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return bool
     */
    protected function isDistinct(QueryBuilder $queryBuilder)
    {
        if ($queryBuilder->getDQLPart('distinct')) {
            return true;
        }

        foreach ($queryBuilder->getDQLPart('select') as $select) {
            $selectString = ltrim(strtolower((string)$select));
            if (strpos($selectString, 'distinct ') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $field
     */
    protected function ensureIdentifierSelected(QueryBuilder $queryBuilder, $field)
    {
        $isSelected = false;
        /** @var Select $select */
        foreach ($queryBuilder->getDQLPart('select') as $select) {
            $selectString = ltrim(strtolower((string)$select));
            if (strpos($selectString, 'distinct ') === 0) {
                $selectString = substr($selectString, 9);
            }
            // if field itself or field with alias
            if ($selectString === $field ||
                (
                    strpos($selectString, $field) === 0 &&
                    strpos(strtolower(ltrim(substr($selectString, strlen($field)))), 'as ') === 0
                )
            ) {
                $isSelected = true;
                break;
            }
        }

        if (!$isSelected) {
            $queryBuilder->addSelect($field);
        }
    }
}
