<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class MergeMassActionListener
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * Remove mass action if entity config mass action disabled
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        if (!isset($config['mass_actions'])
            || empty($config['mass_actions']['merge']['entity_name'])
        ) {
            return;
        }

        $entityClassName = $config['mass_actions']['merge']['entity_name'];
        $entityMergeEnabled = $this->entityConfigProvider->getConfig($entityClassName)->is('enable');
        if (!$entityMergeEnabled) {
            $config->offsetUnsetByPath('[mass_actions][merge]');
        }
    }
}
