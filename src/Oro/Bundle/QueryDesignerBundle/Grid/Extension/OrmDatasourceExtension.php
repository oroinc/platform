<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid\Extension;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;

class OrmDatasourceExtension extends AbstractExtension
{
    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /**
     * @param RestrictionBuilderInterface $restrictionBuilder
     * @param RequestParameters           $requestParams
     */
    public function __construct(
        RestrictionBuilderInterface $restrictionBuilder,
        RequestParameters $requestParams = null
    ) {
        parent::__construct($requestParams);
        $this->restrictionBuilder = $restrictionBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE
        && $config->offsetGetByPath('[source][query_config][filters]');
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        /** @var QueryBuilder $qb */
        $qb      = $datasource->getQueryBuilder();
        $ds      = new GroupingOrmFilterDatasourceAdapter($qb);
        $filters = $config->offsetGetByPath('[source][query_config][filters]');
        $this->restrictionBuilder->buildRestrictions($filters, $ds);
    }
}
