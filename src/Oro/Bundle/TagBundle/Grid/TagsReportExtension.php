<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\QueryDesignerBundle\Grid\QueryDesignerQueryConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Provider\TagVirtualFieldProvider;

class TagsReportExtension extends AbstractTagsExtension
{
    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var JoinIdentifierHelper */
    protected $joinIdentifierHelper;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $entityClassResolver
     * @param TaggableHelper      $helper
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $entityClassResolver,
        TaggableHelper $helper,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        parent::__construct($tagManager, $entityClassResolver);

        $this->taggableHelper      = $helper;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config) &&
            $this->hasTagFields($config) &&
            $this->isUnsupportedGridPrefix($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);
        $columns = $config->offsetGetByPath('[columns]', []);
        $sorters = $config->offsetGetByPath(self::GRID_SORTERS_PATH, []);

        foreach ($this->getTagColumnDefinitions($config) as $tagColumnDefinition) {
            // Replace virtual tags filters and virtual tags columns with properly configured one.
            $idAlias           = $tagColumnDefinition['idAlias'];
            $entityClass       = $tagColumnDefinition['entityClass'];
            $columns[$idAlias] = $this->getColumnDefinition($config, $idAlias, $entityClass);
            $filters[$idAlias] = $this->getColumnFilterDefinition($config, $idAlias, $entityClass);

            // Remove sorter from the grid for the tags column.
            unset($sorters[$idAlias]);
        }

        $config->offsetSetByPath('[columns]', $columns);
        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
        $config->offsetSetByPath(self::GRID_SORTERS_PATH, $sorters);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string
     */
    protected function hasTagFields(DatagridConfiguration $config)
    {
        $columnDefinitions = $this->getTagColumnDefinitions($config);

        return !empty($columnDefinitions);
    }

    /**
     * Gets definition for tag column.
     *
     * @param DatagridConfiguration $config
     * @param string                $idAlias
     * @param string                $entityClass
     *
     * @return array
     */
    protected function getColumnDefinition(DatagridConfiguration $config, $idAlias, $entityClass)
    {
        $columns     = $config->offsetGetByPath('[columns]');
        $label       = isset($columns[$idAlias]['label']) ? $columns[$idAlias]['label'] : 'oro.tag.tags_label';
        $tagColumnId = $this->buildTagColumnId($idAlias, $entityClass);

        return [
            'label'         => $label,
            'type'          => 'callback',
            'frontend_type' => 'tags',
            'callable'      =>
                function (ResultRecordInterface $record) use ($tagColumnId) {
                    return $record->getValue($tagColumnId);
                },
            'editable'      => false,
            'translatable'  => true,
            'renderable'    => true
        ];
    }

    /**
     * Gets definition for tag column filter.
     *
     * @param DatagridConfiguration $config
     * @param string                $idAlias
     * @param string                $entityClass
     *
     * @return array
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config, $idAlias, $entityClass)
    {
        $columns = $config->offsetGetByPath('[columns]');
        $label   = isset($columns[$idAlias]['label']) ? $columns[$idAlias]['label'] : 'oro.tag.tags_label';

        return [
            'type' => 'tag',
            'data_name' => $idAlias,
            'class' => Tag::class,
            'null_value' => ':empty:',
            'populate_default' => true,
            'default_value' => 'Any',
            'label' => $label,
            'enabled' => true,
            'entity_class' => $entityClass
        ];
    }

    /**
     * Returns [['entityClass' => $entityClass, 'idAlias' => $entityIdAlias], ...] for all configured tags columns.
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getTagColumnDefinitions(DatagridConfiguration $config)
    {
        $aliases = $config->offsetGetByPath(QueryDesignerQueryConfiguration::COLUMN_ALIASES);
        if (!$aliases) {
            return [];
        }

        $tagColumns = [];
        $joinIdentifierHelper = $this->getJoinIdentifierHelper();
        foreach ($aliases as $key => $alias) {
            $field = $joinIdentifierHelper->getFieldName($key);
            if ($field === TagVirtualFieldProvider::TAG_FIELD) {
                // get entity class from relations aliases if tag_field configured for relations
                $entityClassName = $joinIdentifierHelper->getEntityClassName($key) ?: parent::getEntity($config);
                $tagColumns[]    = [
                    'idAlias' => $alias,
                    'entityClass' => $entityClassName
                ];
            }
        }

        return $tagColumns;
    }

    /**
     * Returns [$entityClass => $entityIdAlias, ...] for all configured tags columns.
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getTagsColumns(DatagridConfiguration $config)
    {
        $entityIdsFields = [];

        foreach ($this->getTagColumnDefinitions($config) as $columnDefinition) {
            $entityIdsFields[$columnDefinition['entityClass']] = $columnDefinition['idAlias'];
        }

        return $entityIdsFields;
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $identifiers = $this->getTagsColumns($config);
        $rows = $result->getData();
        $entitiesTags = [];
        foreach ($identifiers as $entityClass => $idAlias) {
            $entitiesTags[$entityClass] = $this->getTagsForEntityClass(
                $entityClass,
                $this->extractEntityIds($rows, $idAlias)
            );
        }

        foreach ($entitiesTags as $entityClass => $entityTags) {
            $idAlias = $identifiers[$entityClass];
            $this->addTagsToData($rows, $entityTags, $idAlias, $this->buildTagColumnId($idAlias, $entityClass));
        }
    }

    /**
     * @return JoinIdentifierHelper
     */
    protected function getJoinIdentifierHelper()
    {
        if (null === $this->joinIdentifierHelper) {
            $this->joinIdentifierHelper = new JoinIdentifierHelper(null);
        }

        return $this->joinIdentifierHelper;
    }

    /**
     * Build tag column identifier by entity id alias and entityClass.
     *
     * @param string $idAlias
     * @param string $entityClass
     *
     * @return string
     */
    protected function buildTagColumnId($idAlias, $entityClass)
    {
        return sprintf('%s.%s', $idAlias, $entityClass);
    }
}
