<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class TagsExtension extends AbstractExtension
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

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param TagManager          $tagManager
     * @param TaggableHelper      $helper
     * @param EntityClassResolver $resolver
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        TagManager $tagManager,
        TaggableHelper $helper,
        EntityClassResolver $resolver,
        EntityRoutingHelper $entityRoutingHelper,
        SecurityFacade $securityFacade
    ) {
        $this->tagManager          = $tagManager;
        $this->taggableHelper      = $helper;
        $this->entityClassResolver = $resolver;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->securityFacade      = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            null !== $this->securityFacade->getToken() &&
            null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH) &&
            $this->taggableHelper->isTaggable($this->getEntityClassName($config)) &&
            // Do not add column with tags in cases when user does not have access to view tags, except report/segments.
            ($this->isReportGrid($config) || $this->securityFacade->isGranted('oro_tag_view'));
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $isReports = $this->isReportGrid($config);
        $filters   = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);

        if (!$isReports && empty($filters)) {
            return;
        }

        $columns = $config->offsetGetByPath('[columns]', []);
        $sorters = $config->offsetGetByPath(self::GRID_SORTERS_PATH, []);
        $column  = [self::COLUMN_NAME => $this->getColumnDefinition($config, $isReports)];
        $filter  = $this->getColumnFilterDefinition($config, $isReports);

        if ($isReports) {
            $aliases = $config->offsetGetByPath(self::GRID_COLUMN_ALIAS_PATH);
            if (isset($aliases['tag_field'])) {
                $tagAlias           = $aliases['tag_field'];
                $filters[$tagAlias] = $filter;
                // Need remove old column, as no needed.
                unset($columns[$tagAlias]);
                unset($sorters[$tagAlias]);
                $config->offsetSetByPath(
                    '[columns]',
                    array_merge(
                        $columns,
                        $column
                    )
                );
            }
        } else {
            $filters[self::FILTER_COLUMN_NAME] = $filter;
            $config->offsetSetByPath(
                '[columns]',
                array_merge(
                    $columns,
                    $column
                )
            );
        }

        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
        $config->offsetSetByPath(self::GRID_SORTERS_PATH, $sorters);
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = (array)$result->offsetGetOr('data', []);
        $ids  = array_map(
            function (ResultRecord $item) {
                return $item->getValue('id');
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
                function (ResultRecord $item) use ($tags) {
                    $id   = $item->getValue('id');
                    $data = isset($tags[$id]) ? $tags[$id] : [];
                    $item->addData(['tags' => $data]);

                    return $item;
                },
                $rows
            )
        );
    }

    /**
     * Checks if configuration is for report or segment grid
     *
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function isReportGrid(DatagridConfiguration $config)
    {
        $gridName = $config->offsetGetByPath(self::GRID_NAME_PATH);

        return
            strpos($gridName, 'oro_report') === 0 ||
            strpos($gridName, 'oro_segment') === 0;
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool                  $isReports
     *
     * @return array
     */
    protected function getColumnDefinition(DatagridConfiguration $config, $isReports)
    {
        $className        = $this->getEntityClassName($config);
        $urlSafeClassName = $this->entityRoutingHelper->getUrlSafeClassName($className);

        $permissions = [
            'oro_tag_create'          => $this->securityFacade->isGranted(TagManager::ACL_RESOURCE_CREATE_ID_KEY),
            'oro_tag_unassign_global' => $this->securityFacade->isGranted(TagManager::ACL_RESOURCE_REMOVE_ID_KEY)
        ];

        return [
            'label'          => 'oro.tag.tags_label',
            'type'           => 'callback',
            'frontend_type'  => 'tags',
            'callable'       => function (ResultRecordInterface $record) {
                return $record->getValue('tags');
            },
            'editable'       => false,
            'translatable'   => true,
            'renderable'     => $this->taggableHelper->isEnableGridColumn($className) || $isReports,
            'inline_editing' => [
                'enable'                    => $this->securityFacade->isGranted(
                    TagManager::ACL_RESOURCE_ASSIGN_ID_KEY
                ),
                'editor'                    => [
                    'view'         => 'orotag/js/app/views/editor/tags-editor-view',
                    'view_options' => [
                        'permissions' => $permissions
                    ]
                ],
                'save_api_accessor'         => [
                    'route'                       => 'oro_api_post_taggable',
                    'http_method'                 => 'POST',
                    'default_route_parameters'    => [
                        'entity' => $urlSafeClassName
                    ],
                    'route_parameters_rename_map' => [
                        'id' => 'entityId'
                    ]
                ],
                'autocomplete_api_accessor' => [
                    'class'               => 'oroui/js/tools/search-api-accessor',
                    'search_handler_name' => 'tags',
                    'label_field_name'    => 'name'
                ]
            ]
        ];
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool                  $isReports
     *
     * @return array
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config, $isReports)
    {
        $className = $this->getEntityClassName($config);

        return [
            'type'      => 'tag',
            'data_name' => 'tag.id',
            'label'     => 'oro.tag.entity_plural_label',
            'enabled'   => $this->taggableHelper->isEnableGridFilter($className) || $isReports,
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
}
