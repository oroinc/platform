<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridParams;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class GridParamsExtension extends AbstractExtension
{
    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource();
    }

    #[\Override]
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
