<?php

namespace Oro\Bundle\ActivityBundle\Grid\Extension;

use Doctrine\Common\Collections\Criteria;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * This grid extension provides data for the `contexts` column of ActivityTarget entities
 * Applicable if:
 * - Base entity is one of activity targets
 * - Grid has a column `contexts` defined in configuration
 */
class ContextsExtension extends AbstractExtension
{
    const COLUMN_NAME = 'contexts';
    const COLUMN_PATH = '[columns][contexts]';
    const GRID_FROM_PATH = '[source][query][from]';
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
     * @var string Grid base entity class name
     */
    protected $entityClassName;
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
        $this->entityClassName = $this->getEntityClassName($config);
        $activityTypes = $this->activityManager->getActivityTypes();

        return $config->offsetExistByPath(self::COLUMN_PATH) && in_array($this->entityClassName, $activityTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {

        $columns = $config->offsetGetByPath('[columns]', []);
        $column = [self::COLUMN_NAME => $this->getColumnDefinition($config)];
        $config->offsetSetByPath('[columns]', array_merge($columns, $column));
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $ids = array_map(
            function ($row) {
                return $row->getValue('id');
            },
            $result->getData()
        );

        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->in('id', $ids));

        $results = $this->activityManager
            ->getActivityTargetsQueryBuilder($this->entityClassName, $criteria)
            ->getQuery()
            ->getArrayResult();

        $items = [];

        foreach ($results as $item) {
            $entityConfig = $this->entityConfigProvider->getConfig($item['entity']);
            $item['icon'] = $entityConfig->get('icon');
            $item['link'] = $this->getContextLink($item['entity'], $item['id']);
            $items[$item['ownerId']][] = $item;
        }

        foreach ($result->getData() as $row) {
            /** @var ResultRecord $row */
            $id = $row->getValue('id');
            if (isset($items[$id])) {
                $row->addData([self::COLUMN_NAME => $items[$id]]);
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
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
                return null;
            }

            return $this->router->generate($route, ['id' => $targetId]);
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
        $entityClassName = $config->offsetGetByPath('[extended_entity_name]');
        if (!$entityClassName) {
            $from = $config->offsetGetByPath(self::GRID_FROM_PATH);
            if (!$from) {
                return null;
            }

            $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
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
        return array_merge(
            [
                // defaults
                'label' => 'Contexts',
                'translatable' => false,
                'renderable' => true,
                'template' => self::DEFAULT_TEMPLATE,
            ],
            $config->offsetGetByPath(self::COLUMN_PATH),
            [
                // override any definitions to these keys
                'type' => 'twig',
                'frontend_type' => 'html',
            ]
        );
    }
}
