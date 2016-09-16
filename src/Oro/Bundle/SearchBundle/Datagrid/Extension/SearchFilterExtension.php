<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension;

use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

class SearchFilterExtension extends AbstractFilterExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(Configuration::COLUMNS_PATH);

        if ($filters === null) {
            return false;
        }

        return $config->getDatasourceType() == SearchDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $datasourceAdapter = null;

        if ($datasource instanceof SearchDatasource) {
            $datasourceAdapter = new SearchFilterDatasourceAdapter($datasource->getSearchQuery());
        }

        if ($datasourceAdapter === null) {
            throw new \InvalidArgumentException('Datasource should be an instance of SearchDatasource.');
        }

        $filters = $this->getFiltersToApply($config);
        $values  = $this->getValuesToApply($config);

        foreach ($filters as $filter) {
            $value = isset($values[$filter->getName()]) ? $values[$filter->getName()] : false;

            if ($value !== false) {
                $form = $filter->getForm();

                if (!$form->isSubmitted()) {
                    $form->submit($value);
                }

                if ($form->isValid()) {
                    $data = $form->getData();

                    $filter->apply($datasourceAdapter, $data);
                }
            }
        }
    }
}
