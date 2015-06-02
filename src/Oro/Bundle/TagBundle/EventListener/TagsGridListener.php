<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class TagsGridListener
{
    const GRID_EXTEND_ENTITY_PATH = '[extended_entity_name]';
    const GRID_LEFT_JOIN_PATH = '[source][query][join][left]';
    const GRID_FROM_PATH = '[source][query][from]';
    const GRID_SELECT_PATH = '[source][query][select]';
    const GRID_FILTERS_PATH = '[filters][columns]';
    const GRID_NAME_PATH = 'name';

    const COLUMN_NAME = 'tagname';

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

        $fromParts = $config->offsetGetByPath(self::GRID_FROM_PATH, []);
        $alias     = false;

        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart['table']) == $entityClassName) {
                $alias = $fromPart['alias'];
                break;
            }
        }

        if ($alias === false) {
            // add entity if it not exists in from clause
            $alias       = 'o';
            $fromParts[] = ['table' => $entityClassName, 'alias' => $alias];
            $config->offsetSetByPath(self::GRID_FROM_PATH, $fromParts);
        }

        $leftJoins   = $config->offsetGetByPath(self::GRID_LEFT_JOIN_PATH, []);
        $leftJoins[] = [
            'join'          => 'Oro\Bundle\TagBundle\Entity\Tagging',
            'alias'         => 'tagging',
            'conditionType' => 'WITH',
            'condition'     => sprintf(
                "(tagging.entityName = '%s' and tagging.recordId = %s.id)",
                $entityClassName,
                $alias
            )
        ];
        $leftJoins[] = ['join' => 'tagging.tag', 'alias' => 'tag'];
        $config->offsetSetByPath(self::GRID_LEFT_JOIN_PATH, $leftJoins);

        $select   = $config->offsetGetByPath(self::GRID_SELECT_PATH, []);
        $select[] = 'COUNT(tag.id) as tagsCount';
        $config->offsetSetByPath(self::GRID_SELECT_PATH, $select);

        $filters[self::COLUMN_NAME] = [
            'type'         => 'entity',
            'label'        => 'oro.tag.entity_plural_label',
            'data_name'    => 'tag.id',
            'enabled'      => false,
            'translatable' => true,
            'options'      => [
                'field_type'    => 'oro_tag_entity_tags_selector',
                'field_options' => [
                    'entity_class'         => $entityClassName,
                    'multiple'             => true,
                    'translatable_options' => true
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
