<?php

namespace Oro\Bundle\ActivityBundle\Grid\Extension;

use Doctrine\Common\Collections\Criteria;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Extends ActivityTarget grid with a `contexts` column
 *
 * Applicable if:
 * - Grid has the `contexts` extension enabled
 * - Base entity is one of the activity targets
 * - Datasource is ORM
 *
 * Adds the column with default configuration if it does not exist when enabled.
 * Column type is `twig` and cannot be changed.
 * If `entity_name` option is not specified, tries to guess it from `extended_entity_name` or select's `from` clause
 *
 * Default configuration in datagrid.yml:
 * datagrid:
 *     tasks-grid:
 *         # extension configuration
 *         options:
 *             contexts:
 *                 enabled: true          # default `false`
 *                 column_name: contexts  # optional, column identifier, default is `contexts`
 *                 entity_name: ~         # optional, set the FQCN of the grid base entity if auto detection fails
 *         # column configuration
 *         columns:
 *              contexts:                      # the column name defined in options
 *                 label: oro.contexts.label   # optional
 *                 renderable: true            # optional, default `true`
 *                 ...
 */
class ContextsExtension extends AbstractExtension
{
    const CONTEXTS_ENABLED_PATH = '[options][contexts][enabled]';
    const CONTEXTS_COLUMN_PATH = '[options][contexts][column_name]';
    const CONTEXTS_ENTITY_PATH = '[options][contexts][entity_name]';
    const GRID_EXTENDED_ENTITY_PATH = '[extended_entity_name]';
    const GRID_FROM_PATH = '[source][query][from]';
    const GRID_COLUMNS_PATH = '[columns]';
    const DEFAULT_COLUMN_NAME = 'contexts';
    const DEFAULT_COLUMN_LABEL = 'oro.activity.contexts.column.label';
    const DEFAULT_TEMPLATE = 'OroActivityBundle:Grid:Column/contexts.html.twig';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param GridConfigurationHelper $gridConfigurationHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ActivityManager $activityManager
     * @param ConfigProvider $entityConfigProvider
     * @param RouterInterface $router
     */
    public function __construct(
        GridConfigurationHelper $gridConfigurationHelper,
        EntityClassResolver $entityClassResolver,
        ActivityManager $activityManager,
        ConfigProvider $entityConfigProvider,
        RouterInterface $router
    ) {
        $this->gridConfigurationHelper = $gridConfigurationHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->activityManager = $activityManager;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $isEnabled = !empty($config->offsetGetByPath(self::CONTEXTS_ENABLED_PATH, false));
        $entityClassName = $config->offsetGetByPath(self::CONTEXTS_ENTITY_PATH, $this->getEntityClassName($config));
        $activityTypes = $this->activityManager->getActivityTypes();

        return $isEnabled &&
               OrmDatasource::TYPE == $config->getDatasourceType() &&
               in_array($entityClassName, $activityTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath(self::CONTEXTS_ENTITY_PATH, $this->getEntityClassName($config));
        $config->offsetSetByPath(self::CONTEXTS_ENTITY_PATH, $entityClassName);

        $columnName = $config->offsetGetByPath(self::CONTEXTS_COLUMN_PATH, self::DEFAULT_COLUMN_NAME);
        $config->offsetSetByPath(self::CONTEXTS_COLUMN_PATH, $columnName);

        $columns = $config->offsetGetByPath(self::GRID_COLUMNS_PATH, []);
        $column = [$columnName => $this->getColumnDefinition($config)];
        $config->offsetSetByPath(self::GRID_COLUMNS_PATH, array_merge($columns, $column));
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $ids = array_map(
            function (ResultRecord $row) {
                return $row->getValue('id');
            },
            $result->getData()
        );

        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->in('id', $ids));

        $entityClassName = $config->offsetGetByPath(self::CONTEXTS_ENTITY_PATH);
        $columnName = $config->offsetGetByPath(self::CONTEXTS_COLUMN_PATH);

        $items = $this->activityManager
            ->getActivityTargetsQueryBuilder($entityClassName, $criteria)
            ->addOrderBy('entity', 'ASC')
            ->addOrderBy('title', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $contexts = [];

        foreach ($items as $item) {
            $entityConfig = $this->entityConfigProvider->getConfig($item['entity']);
            $item['icon'] = $entityConfig->get('icon');
            $item['link'] = $this->getContextLink($item['entity'], $item['id']);
            $contexts[$item['ownerId']][] = $item;
        }

        foreach ($result->getData() as $row) {
            /** @var ResultRecord $row */
            $id = $row->getValue('id');
            if (isset($contexts[$id])) {
                $row->addData([$columnName => $contexts[$id]]);
            }
        }
    }

    /**
     * Get a 'view' link for entity
     *
     * @param string $targetClass The FQCN of the target entity
     * @param int $targetId       The identifier of the target entity
     *
     * @return string|null
     */
    protected function getContextLink($targetClass, $targetId)
    {
        if (ExtendHelper::isCustomEntity($targetClass)) {
            $safeClassName = str_replace('\\', '_', $targetClass);

            // Generate view link for the custom entity
            return $this->router->generate('oro_entity_view', ['id' => $targetId, 'entityName' => $safeClassName]);
        }

        $metadata = $this->entityConfigProvider->getConfigManager()->getEntityMetadata($targetClass);

        if ($metadata) {
            try {
                $route = $metadata->getRoute('view', true);

                return $this->router->generate($route, ['id' => $targetId]);
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
            }
        }

        return null;
    }

    /**
     * Try to guess the grid's base entity class name
     *
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getEntityClassName(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath(self::GRID_EXTENDED_ENTITY_PATH);

        if (!$entityClassName) {
            $from = $config->offsetGetByPath(self::GRID_FROM_PATH);
            if (!$from) {
                $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
            }
        }

        return $entityClassName;
    }

    /**
     * Gets definition for contexts column
     *
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getColumnDefinition(DatagridConfiguration $config)
    {
        $columnDefinitionPath = sprintf(
            '%s[%s]',
            self::GRID_COLUMNS_PATH,
            $config->offsetGetByPath(self::CONTEXTS_COLUMN_PATH)
        );

        return array_merge(
            [
                // defaults
                'label' => self::DEFAULT_COLUMN_LABEL,
                'translatable' => false,
                'renderable' => true,
                'template' => self::DEFAULT_TEMPLATE,
            ],
            $config->offsetGetByPath($columnDefinitionPath, []),
            [
                // override any definitions to these keys
                'type' => 'twig',
                'frontend_type' => 'html',
            ]
        );
    }
}
