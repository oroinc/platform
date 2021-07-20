<?php

namespace Oro\Bundle\ReportBundle\Grid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Normalization currency format to decimal for report grid
 */
class ColumnCurrencyNormalization
{
    public function onBuildBefore(BuildBefore $event)
    {
        $dataGrid = $event->getDatagrid();
        if (strstr($dataGrid->getName(), Report::GRID_PREFIX)) {
            $config = $event->getConfig();
            $params = $config->toArray();
            $columns = $params['columns'];
            foreach ($columns as $key => $column) {
                if (isset($column['frontend_type']) && $column['frontend_type'] === PropertyInterface::TYPE_CURRENCY) {
                    $columns[$key]['frontend_type'] = PropertyInterface::TYPE_DECIMAL;
                }
            }

            $config->offsetSet('columns', $columns);
        }
    }
}
