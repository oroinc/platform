<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagsExtension extends AbstractTagsExtension
{
    const TAGS_ROOT_PARAM = '_tags';
    const DISABLED_PARAM  = '_disabled';

    const COLUMN_NAME = 'tags';

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param TagManager                    $tagManager
     * @param EntityClassResolver           $entityClassResolver
     * @param TaggableHelper                $helper
     * @param EntityRoutingHelper           $entityRoutingHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $entityClassResolver,
        TaggableHelper $helper,
        EntityRoutingHelper $entityRoutingHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($tagManager, $entityClassResolver);

        $this->taggableHelper = $helper;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
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
            !$this->isDisabled() &&
            !$this->isUnsupportedGridPrefix($config) &&
            $this->isGridRootEntityTaggable($config) &&
            null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH) &&
            null !== $this->tokenStorage->getToken() &&
            $this->authorizationChecker->isGranted('oro_tag_view');
    }

    /**
     * @return bool
     */
    protected function isDisabled()
    {
        $tagParameters = $this->getParameters()->get(self::TAGS_ROOT_PARAM);

        return
            $tagParameters &&
            !empty($tagParameters[self::DISABLED_PARAM]);
    }

    /**
     * @param DatagridConfiguration $configuration
     *
     * @return bool
     */
    protected function isGridRootEntityTaggable(DatagridConfiguration $configuration)
    {
        $className = $this->getEntity($configuration);

        return $className && $this->taggableHelper->isTaggable($className);
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
     * Gets definition for tag column.
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getColumnDefinition(DatagridConfiguration $config)
    {
        $className        = $this->getEntity($config);
        $urlSafeClassName = $this->entityRoutingHelper->getUrlSafeClassName($className);

        $permissions = [
            'oro_tag_create' => $this->authorizationChecker->isGranted(TagManager::ACL_RESOURCE_CREATE_ID_KEY)
        ];

        return [
            'label'          => 'oro.tag.tags_label',
            'type'           => 'callback',
            'frontend_type'  => 'tags',
            'callable'       => function (ResultRecordInterface $record) {
                return $record->getValue(self::COLUMN_NAME);
            },
            'editable'       => false,
            'translatable'   => true,
            'renderable'     => $this->taggableHelper->isEnableGridColumn($className),
            'inline_editing' => [
                'enable'                    => $this->authorizationChecker->isGranted(
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
     * Gets definition for tag column filter.
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getColumnFilterDefinition(DatagridConfiguration $config)
    {
        $className = $this->getEntity($config);
        $dataName = sprintf('%s.%s', $config->getOrmQuery()->getRootAlias(), 'id');
        $enabled = $this->taggableHelper->isEnableGridFilter($className);
        return [
            'type' => 'tag',
            'data_name' => $dataName,
            'class' => Tag::class,
            'null_value' => ':empty:',
            'populate_default' => true,
            'default_value' => 'Any',
            'label' => 'oro.tag.entity_plural_label',
            'enabled' => $enabled,
            'entity_class' => $className
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows    = $result->getData();
        $idField = 'id';
        $tags    = $this->getTagsForEntityClass(
            $this->getEntity($config),
            $this->extractEntityIds($rows, $idField)
        );

        $this->addTagsToData($rows, $tags, $idField, self::COLUMN_NAME);
    }
}
