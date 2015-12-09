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
    const GRID_FILTERS_PATH       = '[filters][columns]';
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
            $this->securityFacade->getToken() !== null &&
            null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH)
            && $this->taggableHelper->isTaggable($this->getEntityClassName($config));
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        // Skip adding column and filter on reports and segments.
        // @todo: Should be refactored and added filter and column in CRM-4731
        if (strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_report') === 0 ||
            strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_segment') === 0
        ) {
            return;
        }

        $columns          = $config->offsetGetByPath('[columns]') ?: [];
        $className        = $this->getEntityClassName($config);
        $urlSafeClassName = $this->entityRoutingHelper->getUrlSafeClassName($className);

        $permissions = [
            'oro_tag_create'          => $this->securityFacade->isGranted(TagManager::ACL_RESOURCE_CREATE_ID_KEY),
            'oro_tag_unassign_global' => $this->securityFacade->isGranted(TagManager::ACL_RESOURCE_REMOVE_ID_KEY)
        ];

        $config->offsetSetByPath(
            '[columns]',
            array_merge(
                $columns,
                [
                    self::COLUMN_NAME => [
                        'label'          => 'oro.tag.tags_label',
                        'type'           => 'callback',
                        'frontend_type'  => 'tags',
                        'callable'       => function (ResultRecordInterface $record) {
                            return $record->getValue('tags');
                        },
                        'editable'       => false,
                        'translatable'   => true,
                        'renderable'     => $this->taggableHelper->isEnableGridColumn($className),
                        'inline_editing' => [
                            'enable' => $this->securityFacade->isGranted(TagManager::ACL_RESOURCE_ASSIGN_ID_KEY),
                            'editor' => [
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
                    ]
                ]
            )
        );

        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);

        // @todo: Should be refactored and added filter in CRM-4731
        // For reports and segments we need to add filter also if $filters is empty.
        if (empty($filters)) {
            return;
        }

        $filters[self::FILTER_COLUMN_NAME] = [
            'type'      => 'tag',
            'label'     => 'oro.tag.entity_plural_label',
            'data_name' => 'tag.id',
            'enabled'   => $this->taggableHelper->isEnableGridFilter($className),
            'options'   => [
                'field_options' => [
                    'entity_class' => $this->getEntityClassName($config),
                ]
            ]
        ];

        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
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
                    $id = $item->getValue('id');
                    $data = isset($tags[$id]) ? $tags[$id] : [];
                    $item->addData(['tags' => $data]);

                    return $item;
                },
                $rows
            )
        );
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
