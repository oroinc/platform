<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Translation;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class WorkflowColumnListener
{
    const COLUMN_NAME = 'workflow';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $this->processFilters($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processFilters(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath('[filters][columns]', []);

        $filters[self::COLUMN_NAME] =  [
            'label' => 'oro.workflow.translation.workflow.label',
            'type' => 'workflow',
            'data_name' => 'translationKey',
        ];

        $config->offsetSetByPath('[filters][columns]', $filters);
    }
}
