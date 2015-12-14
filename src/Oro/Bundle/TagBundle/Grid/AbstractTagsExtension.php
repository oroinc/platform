<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\TagManager;

abstract class AbstractTagsExtension extends AbstractExtension
{
    const COLUMN_NAME = 'tags';

    const GRID_EXTEND_ENTITY_PATH = '[extended_entity_name]';
    const GRID_FROM_PATH          = '[source][query][from]';
    const GRID_COLUMN_ALIAS_PATH  = '[source][query_config][column_aliases]';
    const GRID_FILTERS_PATH       = '[filters][columns]';
    const GRID_SORTERS_PATH       = '[sorters][columns]';
    const GRID_NAME_PATH          = 'name';
    const FILTER_COLUMN_NAME      = 'tagname';
    const PROPERTY_ID_PATH        = '[properties][id]';

    /** @var TagManager */
    protected $tagManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(TagManager $tagManager, EntityClassResolver $entityClassResolver)
    {
        $this->tagManager          = $tagManager;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Checks if configuration is for report or segment grid
     *
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function isReportOrSegmentGrid(DatagridConfiguration $config)
    {
        $gridName = $config->offsetGetByPath(self::GRID_NAME_PATH);

        return
            strpos($gridName, 'oro_report') === 0 ||
            strpos($gridName, 'oro_segment') === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $identifier = $this->getTagFieldAlias($config);

        $rows = (array)$result->offsetGetOr('data', []);
        $ids  = array_map(
            function (ResultRecord $item) use ($identifier) {
                return $item->getValue($identifier);
            },
            $rows
        );

        $tags = array_reduce(
            $this->tagManager->getTagsByEntityIds($this->getEntityClassName($config), $ids),
            function ($entitiesTags, $item) {
                $entitiesTags[$item['entityId']][] = $item;

                return $entitiesTags;
            },
            []
        );

        $result->offsetSet(
            'data',
            array_map(
                function (ResultRecord $item) use ($tags, $identifier) {
                    $id   = $item->getValue($identifier);
                    $data = isset($tags[$id]) ? $tags[$id] : [];
                    $item->addData(['tags' => $data]);

                    return $item;
                },
                $rows
            )
        );
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getEntityClassName(DatagridConfiguration $config)
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

    /**
     * Gets definition for tag column
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    abstract protected function getColumnDefinition(DatagridConfiguration $config);

    /**
     * Gets definition for tag column filter
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    abstract protected function getColumnFilterDefinition(DatagridConfiguration $config);

    /**
     * Gets alias for the tag field
     *
     * @param DatagridConfiguration $config
     *
     * @return string
     */
    abstract protected function getTagFieldAlias(DatagridConfiguration $config);
}
