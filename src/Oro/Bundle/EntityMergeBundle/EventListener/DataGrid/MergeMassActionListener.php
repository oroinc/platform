<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Handles the configuration of merge mass actions in datagrids.
 *
 * Listens to datagrid build events and removes the merge mass action if the entity
 * configuration has merge functionality disabled. This ensures that the merge action
 * is only available for entities that have merge enabled in their entity configuration.
 */
class MergeMassActionListener
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * Remove mass action if entity config mass action disabled
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        if (
            !isset($config['mass_actions'])
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
