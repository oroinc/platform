<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class MergeMassActionListener
{
    /**
     * @var MetadataRegistry
     */
    protected $metadataRegistry;

    /**
     * @param MetadataRegistry $metadataRegistry
     */
    public function __construct(MetadataRegistry $metadataRegistry)
    {
        $this->metadataRegistry = $metadataRegistry;
    }

    /**
     * Remove mass action if entity config mass action disabled
     *
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        if (!isset($config['mass_actions']) ||
            !isset($config['mass_actions']['merge']) ||
            empty($config['mass_actions']['merge']['entity_name'])
        ) {
            return;
        }

        $entityName = $config['mass_actions']['merge']['entity_name'];

        $entityMergeEnable = $this->metadataRegistry->getEntityMetadata($entityName)->is('enable', true);

        if (!$entityMergeEnable) {
            $config->offsetUnsetByPath('[mass_actions][merge]');
        }
    }
}
