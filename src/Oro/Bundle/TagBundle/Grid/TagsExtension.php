<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class TagsExtension extends AbstractTagsExtension
{
    const ROOT_PARAM = '_tags';
    const DISABLED_PARAM   = '_disabled';

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $resolver
     * @param TaggableHelper      $helper
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $resolver,
        TaggableHelper $helper,
        EntityRoutingHelper $entityRoutingHelper,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($tagManager, $resolver);

        $this->taggableHelper      = $helper;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->securityFacade      = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $tagParameters = $this->getParameters()->get(self::ROOT_PARAM);
        $isDisabled = $tagParameters && !empty($tagParameters[self::DISABLED_PARAM]);

        return !$isDisabled
            && !$this->isReportOrSegmentGrid($config)
            && $this->isGridRootEntityTaggable($config)
            && null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH)
            && $this->isAccessGranted();
    }

    /**
     * @param DatagridConfiguration $configuration
     *
     * @return bool
     */
    protected function isGridRootEntityTaggable(DatagridConfiguration $configuration)
    {
        $className = $this->getEntityClassName($configuration);
        return $className && $this->taggableHelper->isTaggable($className);
    }

    /**
     * @return bool
     */
    protected function isAccessGranted()
    {
        return null !== $this->securityFacade->getToken() && $this->securityFacade->isGranted('oro_tag_view');
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath('[columns]', []);
        $column  = [self::COLUMN_NAME => $this->getColumnDefinition($config)];
        $config->offsetSetByPath('[columns]', array_merge($columns, $column));

        // do not add tag filter if $filters are empty(case when they are disabled).
        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);
        if (!empty($filters)) {
            $filters[self::FILTER_COLUMN_NAME] = $this->getColumnFilterDefinition($config);
            $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
        }
    }

    /**
     * {@inheritdoc}
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
            'renderable'     => $this->taggableHelper->isEnableGridColumn($className),
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
     * {@inheritdoc}
     */
    protected function getTagFieldAlias(DatagridConfiguration $config)
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config)
    {
        $className = $this->getEntityClassName($config);

        return [
            'type'      => 'tag',
            'data_name' => 'tag.id',
            'label'     => 'oro.tag.entity_plural_label',
            'enabled'   => $this->taggableHelper->isEnableGridFilter($className),
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
