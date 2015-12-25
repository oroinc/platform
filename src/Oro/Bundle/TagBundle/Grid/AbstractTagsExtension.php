<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\TagManager;

abstract class AbstractTagsExtension extends AbstractExtension
{
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

    protected function getTagsForEntityClass($entityClass, array $ids)
    {
        return array_reduce(
            $this->tagManager->getTagsByEntityIds($entityClass, $ids),
            function ($tags, $item) {
                $tags[$item['entityId']][] = $item;

                return $tags;
            },
            []
        );
    }

    /**
     * Extract entity ids from rows by identifier.
     * @param array  $rows
     * @param string $idField
     *
     * @return array
     */
    protected function extractEntityIds(array $rows, $idField)
    {
        return array_reduce(
            $rows,
            function ($entityIds, ResultRecord $item) use ($idField) {
                $entityIds[] = $item->getValue($idField);

                return $entityIds;
            },
            []
        );
    }

    /**
     * Add tags data to result rows for every entity id founded in tags array.
     *
     * @param array  $rows
     * @param array  $tags
     * @param string $identifier
     * @param string $tagsColumnId
     *
     * @return array
     */
    protected function addTagsToData(array  $rows, array $tags, $identifier, $tagsColumnId)
    {
        return array_map(
            function (ResultRecord $item) use ($tags, $identifier, $tagsColumnId) {
                $id   = $item->getValue($identifier);
                $data = isset($tags[$id]) ? $tags[$id] : [];
                $item->addData([$tagsColumnId => $data]);

                return $item;
            },
            $rows
        );
    }
}
