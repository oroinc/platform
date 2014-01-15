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

    /**
     * @var QueryBuilder
     */
    protected $masterQB;

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
        $this->masterQB = clone $datasource->getQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $totals = $this->getTotals($config);
        $totalQueries = [];
        foreach ($totals as $field => $total) {
            if (isset($total['query'])) {
                $totalQueries[] = $total['query'] . ' AS ' . $field;
            }
        };

        $ids = [];
        foreach ($result['data'] as $res) {
            $ids[] = $res['id'];
        };

        $data = $this->masterQB
            ->select($totalQueries)
            ->andWhere($this->masterQB->expr()->in($this->masterQB->getRootAliases()[0].'.id', $ids))
            ->getQuery()
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getScalarResult();

        if (!empty($data)) {
            foreach ($totals as $field => &$total) {
                if (isset($data[0][$field])) {
                    $total['total'] = $data[0][$field];
                }
            };
        }

        $result->offsetAddToArray('options', ['totals' => $totals]);

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
}
