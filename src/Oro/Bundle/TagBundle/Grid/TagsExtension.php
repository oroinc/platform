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
            null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH)
            && $this->taggableHelper->isTaggable($this->getEntityClassName($config));
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $isReports = strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_report') === 0 ||
            strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_segment') === 0;

        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);

        if (!$isReports && empty($filters)) {
            return;
        }

        $columns = $config->offsetGetByPath('[columns]', []);
        $sorters = $config->offsetGetByPath(self::GRID_SORTERS_PATH, []);
        $column = [self::COLUMN_NAME => $this->getColumnDefinition($config)];
        $filter = $this->getColumnFilterDefinition($config);

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
        //@TODO Should we apply acl?
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
                    $entityTags = [];
                    $id = $item->getValue('id');
                    if (isset($tags[$id])) {
                        $entityTags = $tags[$id];
                    }
                    $item->addData(['tags' => $entityTags]);
                    return $item;
                },
                $rows
            )
        );
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getColumnDefinition(DatagridConfiguration $config)
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
            'renderable'     => true,
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
     *
     * @return array
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config)
    {
        return [
            'type'      => 'tag',
            'data_name' => 'tag.id',
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
     * @return null|string
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
