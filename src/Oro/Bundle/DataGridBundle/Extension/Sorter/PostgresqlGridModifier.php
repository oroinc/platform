<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class PostgresqlGridModifier extends AbstractExtension
{
    /** @var ContainerInterface */
    protected $container;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param ContainerInterface $container
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(ContainerInterface $container, EntityClassResolver $entityClassResolver)
    {
        $this->container = $container;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $dbDriver = $this->container->getParameter('database_driver');
        return $dbDriver === DatabaseDriverInterface::DRIVER_POSTGRESQL;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return -251;
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
        $identifier = null;
        $entityClassName = $this->getEntity($config);

        if (!$entityClassName) {
            return;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $datasource->getQueryBuilder();
        $fromParts = $queryBuilder->getDQLPart('from');
        $alias = false;

        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($entityClassName);
        if ($metadata) {
            $identifier = $metadata->getIdentifier()[0];
        }

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart->getFrom()) == $entityClassName) {
                $alias = $fromPart->getAlias();
                break;
            }
        }

        if ($alias && $identifier) {
            $field = $alias . '.' . $identifier;
            $orderBy = $queryBuilder->getDQLPart('orderBy');
            if (!isset($orderBy[$field])) {
                $queryBuilder->addOrderBy($field, 'ASC');
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath('[extended_entity_name]');
        if (!$entityClassName) {
            $from = $config->offsetGetByPath('[source][query][from]');
            if (!$from) {
                return null;
            }

            $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
        }

        return $entityClassName;
    }
}
