<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridParams;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * @TODO: should be refactored in BAP-6849
 */
class GridParamsExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $params = $this->getParameters()->all();
        $gridParams = array_filter(
            $params,
            function ($param) {
                return !is_array($param) && !is_null($param);
            }
        );

        $data->offsetAddToArray('gridParams', $gridParams);
    }
}
