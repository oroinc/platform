<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Provider\TagVirtualFieldProvider;

class TagsReportExtension extends AbstractTagsExtension
{
    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $resolver
     * @param TaggableHelper      $helper
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $resolver,
        TaggableHelper $helper,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        parent::__construct($tagManager, $resolver);

        $this->taggableHelper      = $helper;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            $this->hasTagFieldAlias($config) &&
            $this->isReportOrSegmentGrid($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);
        $columns = $config->offsetGetByPath('[columns]', []);
        $sorters = $config->offsetGetByPath(self::GRID_SORTERS_PATH, []);

        $column = [self::COLUMN_NAME => $this->getColumnDefinition($config)];
        $filter = $this->getColumnFilterDefinition($config);

        // Replace virtual tags filter and virtual tags column with properly configured one.
        $tagAlias           = $this->getTagFieldAlias($config);
        $filters[$tagAlias] = $filter;
        unset($columns[$tagAlias]);
        unset($sorters[$tagAlias]);

        $config->offsetSetByPath('[columns]', array_merge($columns, $column));
        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
        $config->offsetSetByPath(self::GRID_SORTERS_PATH, $sorters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTagFieldAlias(DatagridConfiguration $config)
    {
        $aliases = $config->offsetGetByPath(self::GRID_COLUMN_ALIAS_PATH);

        return $aliases[TagVirtualFieldProvider::TAG_FIELD];
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string
     */
    protected function hasTagFieldAlias(DatagridConfiguration $config)
    {
        $aliases = $config->offsetGetByPath(self::GRID_COLUMN_ALIAS_PATH);

        return isset($aliases[TagVirtualFieldProvider::TAG_FIELD]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinition(DatagridConfiguration $config)
    {
        return [
            'label'         => 'oro.tag.tags_label',
            'type'          => 'callback',
            'frontend_type' => 'tags',
            'callable'      =>
                function (ResultRecordInterface $record) {
                    return $record->getValue('tags');
                },
            'editable'      => false,
            'translatable'  => true,
            'renderable'    => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config)
    {
        return [
            'type'      => 'tag',
            'data_name' => 'tag.id',
            'label'     => 'oro.tag.entity_plural_label',
            'enabled'   => true,
            'options'   => [
                'field_options' => [
                    'entity_class' => $this->getEntityClassName($config),
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }
}
