<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\QueryDesignerBundle\Grid\QueryDesignerQueryConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;

class OrmDatasourceExtension extends AbstractExtension
{
    /** @deprecated since 1.10. Use config->getName() instead */
    const NAME_PATH = '[name]';

    /**
     * @var string[]
     */
    protected $appliedFor;

    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /**
     * @param RestrictionBuilderInterface $restrictionBuilder
     */
    public function __construct(RestrictionBuilderInterface $restrictionBuilder)
    {
        $this->restrictionBuilder = $restrictionBuilder;
        $this->parameters = new ParameterBag();
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && $config->offsetGetByPath(QueryDesignerQueryConfiguration::FILTERS);
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $gridName = $config->getName();
        $parametersKey = md5(json_encode($this->parameters->all()));

        if (!empty($this->appliedFor[$gridName . $parametersKey])) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb      = $datasource->getQueryBuilder();
        $ds      = $this->createDatasourceAdapter($qb);
        $filters = $config->offsetGetByPath(QueryDesignerQueryConfiguration::FILTERS);
        $this->restrictionBuilder->buildRestrictions($filters, $ds);
        $this->appliedFor[$gridName . $parametersKey] = true;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return GroupingOrmFilterDatasourceAdapter
     */
    protected function createDatasourceAdapter(QueryBuilder $qb)
    {
        return new GroupingOrmFilterDatasourceAdapter($qb);
    }
}
