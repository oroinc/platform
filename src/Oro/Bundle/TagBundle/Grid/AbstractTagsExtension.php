<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\UnsupportedGridPrefixesTrait;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Component\PhpUtils\ArrayUtil;

abstract class AbstractTagsExtension extends AbstractExtension
{
    use UnsupportedGridPrefixesTrait;

    const GRID_FILTERS_PATH = '[filters][columns]';
    const GRID_SORTERS_PATH = '[sorters][columns]';
    /** @deprecated since 1.10. Use config->getName() instead */
    const GRID_NAME_PATH     = 'name';
    const FILTER_COLUMN_NAME = 'tagname';
    const PROPERTY_ID_PATH   = '[properties][id]';

    /** @var TagManager */
    protected $tagManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var string|null */
    protected $entityClassName;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $entityClassResolver
    ) {
        $this->tagManager = $tagManager;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource();
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        if ($this->entityClassName === null) {
            $this->entityClassName = $config->getOrmQuery()->getRootEntity($this->entityClassResolver, true);
        }

        return $this->entityClassName;
    }

    /**
     * @param string $entityClass
     * @param array $ids
     * @return mixed
     */
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
