<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;

class OrmTotalsExtension extends AbstractExtension
{
    /** @var RequestParameters */
    protected $requestParams;

    /** @var  Translator */
    protected $translator;

    protected $totals;

    public function __construct(
        RequestParameters $requestParams = null,
        Translator $translator
    ) {
        $this->requestParams = $requestParams;
        $this->translator    = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $columns      = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        $isApplicable = $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) === OrmDatasource::TYPE
            && is_array($columns);

        return $isApplicable;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->validateConfiguration(
            new Configuration(),
            ['footer' => $config->offsetGetByPath(Configuration::SORTERS_PATH)]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $totals = $this->getTotalsToApply($config, $datasource);

        /*
        $multisort = $config->offsetGetByPath(Configuration::MULTISORT_PATH, false);
        foreach ($sorters as $definition) {
            list($direction, $sorter) = $definition;

            $sortKey = $sorter['data_name'];

            // if need customized behavior, just pass closure under "apply_callback" node
            if (isset($sorter['apply_callback']) && is_callable($sorter['apply_callback'])) {
                $sorter['apply_callback']($datasource, $sortKey, $direction);
            } else {
                $datasource->getQueryBuilder()->addOrderBy($sortKey, $direction);
            }
        }*/
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $result->offsetAddToArray('options', ['totals' => $this->totals]);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $totals = $this->getTotals($config);

        foreach ($data->offsetGetOr('columns', []) as $key => $column) {
            if (isset($column['name']) && isset($totals[$column['name']])) {
                $totals[$column['name']]['label'] = $this->translator->trans($totals[$column['name']]['label']);
                $proceed[] = $column['name'];
            }
        }

        $data
            ->offsetAddToArray('state', ['totals' => $totals])
            ->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['oro/datagrid/totals-builder']);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // should visit after all extensions
        return -250;
    }

    /**
     * Retrieve and prepare list of sorters
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getTotals(DatagridConfiguration $config)
    {
        $totals = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        foreach ($totals as $name => $definition) {
            $definition     = is_array($definition) ? $definition : [];
            $sorters[$name] = $definition;
        }

        return $totals;
    }

    /**
     * Prepare sorters array
     *
     * @param DatagridConfiguration $config
     *
     * @param DatasourceInterface $datasource
     * @return array
     */
    protected function getTotalsToApply(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $totals = $this->getTotals($config);
        /** @var QueryBuilder $qb */
        $qb = clone $datasource->getQueryBuilder();

        $totalSelects = [];
        foreach ($totals as $field => $total) {
            if (isset($total['query'])) {
                $totalSelects[] = $total['query'] . ' as ' . $field;
            }
        };
        $qb->select($totalSelects);

        $data = $qb->getQuery()->getScalarResult();

        if (!empty($data)) {
            foreach ($totals as $field => &$total) {
                if (isset($data[0][$field])) {
                    $total['total'] = $data[0][$field];
                }
            };
        }

        $this->totals = $totals;

        return $totals;
    }
}
