<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

class OrmFilterExtension extends AbstractFilterExtension
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

        return $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $filters = $this->getFiltersToApply($config);
        $values  = $this->getValuesToApply($config);
        /** @var OrmDatasource $datasource */
        $countQb        = $datasource->getCountQb();
        $countQbAdapter = null;
        if ($countQb) {
            $countQbAdapter = new OrmFilterDatasourceAdapter($countQb);
        }
        $datasourceAdapter = new OrmFilterDatasourceAdapter($datasource->getQueryBuilder());

        foreach ($filters as $filter) {
            $value = isset($values[$filter->getName()]) ? $values[$filter->getName()] : false;

            if ($value !== false) {
                $form = $filter->getForm();
                if (!$form->isSubmitted()) {
                    $form->submit($value);
                }

                if ($form->isValid()) {
                    $data = $form->getData();
                    if (isset($value['value']['start'])) {
                        $data['value']['start_original'] = $value['value']['start'];
                    }
                    if (isset($value['value']['end'])) {
                        $data['value']['end_original'] = $value['value']['end'];
                    }
                    $filter->apply($datasourceAdapter, $data);
                    if ($countQbAdapter) {
                        $filter->apply($countQbAdapter, $data);
                    }
                }
            }
        }
    }
}
