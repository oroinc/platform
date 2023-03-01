<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration as InlineEditingConfiguration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingConfigurator;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Enables grid inline editing feature to provide tags inline editing.
 * Adds tag column and filter for entities with enabled tag functionality.
 */
class TagsExtension extends AbstractTagsExtension
{
    public const TAGS_ROOT_PARAM = '_tags';
    public const DISABLED_PARAM = '_disabled';

    private const COLUMN_NAME = 'tags';

    private TaggableHelper $taggableHelper;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;
    private InlineEditingConfigurator $inlineEditingConfigurator;
    private FeatureChecker $featureChecker;

    public function __construct(
        TagManager $tagManager,
        EntityClassResolver $entityClassResolver,
        TaggableHelper $helper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        InlineEditingConfigurator $inlineEditingConfigurator,
        FeatureChecker $featureChecker
    ) {
        parent::__construct($tagManager, $entityClassResolver);

        $this->taggableHelper = $helper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->inlineEditingConfigurator = $inlineEditingConfigurator;
        $this->featureChecker = $featureChecker;
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
            parent::isApplicable($config)
            && $this->featureChecker->isFeatureEnabled('manage_tags')
            && !$this->isDisabled()
            && !$this->isUnsupportedGridPrefix($config)
            && $this->isGridRootEntityTaggable($config)
            && null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH)
            && null !== $this->tokenStorage->getToken()
            && $this->authorizationChecker->isGranted('oro_tag_view');
    }

    /**
     * @return bool
     */
    protected function isDisabled(): bool
    {
        $tagParameters = $this->getParameters()->get(self::TAGS_ROOT_PARAM);

        return !empty($tagParameters[self::DISABLED_PARAM]);
    }

    /**
     * @param DatagridConfiguration $configuration
     *
     * @return bool
     */
    protected function isGridRootEntityTaggable(DatagridConfiguration $configuration): bool
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
        $column = [self::COLUMN_NAME => $this->getColumnDefinition($config)];
        $config->offsetSetByPath('[columns]', array_merge($columns, $column));

        // do not add tag filter if $filters are empty(case when they are disabled).
        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);
        if (!empty($filters)) {
            $filters[self::FILTER_COLUMN_NAME] = $this->getColumnFilterDefinition($config);
            $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
        }

        $this->enableInlineEditing($config);
    }

    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        if ($this->inlineEditingConfigurator->isInlineEditingSupported($config)) {
            $data->offsetSet(
                InlineEditingConfiguration::BASE_CONFIG_KEY,
                $config->offsetGetOr(InlineEditingConfiguration::BASE_CONFIG_KEY, [])
            );
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
        $className = $this->getEntity($config);

        return [
            'label' => 'oro.tag.tags_label',
            'type' => 'callback',
            'frontend_type' => 'tags',
            'callable' => function (ResultRecordInterface $record) {
                return $record->getValue(self::COLUMN_NAME);
            },
            'editable' => false,
            'translatable' => true,
            'notMarkAsBlank' => true,
            'renderable' => $this->taggableHelper->isEnableGridColumn($className)
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
        $rows = $result->getData();
        $idField = 'id';
        $tags = $this->getTagsForEntityClass(
            $this->getEntity($config),
            $this->extractEntityIds($rows, $idField)
        );

        $this->addTagsToData($rows, $tags, $idField, self::COLUMN_NAME);
    }

    private function enableInlineEditing(DatagridConfiguration $config): void
    {
        if ($this->inlineEditingConfigurator->isInlineEditingSupported($config)
            && !$config->offsetGetByPath(InlineEditingConfiguration::ENABLED_CONFIG_PATH)
        ) {
            $config->offsetSetByPath(InlineEditingConfiguration::ENABLED_CONFIG_PATH, true);
            $this->inlineEditingConfigurator->configureInlineEditingForGrid($config);
            $this->inlineEditingConfigurator->configureInlineEditingForColumn($config, self::COLUMN_NAME);
        }
    }
}
