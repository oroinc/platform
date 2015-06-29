<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class TagsGridListener
{
    const GRID_EXTEND_ENTITY_PATH = '[extended_entity_name]';
    const GRID_FROM_PATH          = '[source][query][from]';
    const GRID_FILTERS_PATH       = '[filters][columns]';
    const GRID_NAME_PATH          = 'name';
    const COLUMN_NAME             = 'tagname';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config          = $event->getConfig();
        $entityClassName = $this->getEntity($config);
        $filters         = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);

        if (empty($filters)
            || !is_a($entityClassName, 'Oro\Bundle\TagBundle\Entity\Taggable', true)
            || strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_report') === 0
        ) {
            return;
        }

        $filters[self::COLUMN_NAME] = [
            'type'      => 'tag',
            'label'     => 'oro.tag.entity_plural_label',
            'data_name' => 'tag.id',
            'enabled'   => false,
            'options'   => [
                'field_options' => [
                    'entity_class' => $entityClassName,
                ]
            ]
        ];
        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath(self::GRID_EXTEND_ENTITY_PATH);
        if (!$entityClassName) {
            $from = $config->offsetGetByPath(self::GRID_FROM_PATH);
            if (!$from) {
                return null;
            }

            $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
        }

        return $entityClassName;
    }
}
