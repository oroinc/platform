<?php

namespace Oro\Bundle\ActivityBundle\Grid\Extension;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityContextApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

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

    /** @var ActivityContextApiEntityManager */
    protected $contextManager;

    protected $entityClassName;

    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param GridConfigurationHelper $gridConfigurationHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ActivityManager $activityManager
     * @param ActivityContextApiEntityManager $contextManager
     */
    public function __construct(
        GridConfigurationHelper $gridConfigurationHelper,
        EntityClassResolver $entityClassResolver,
        ActivityManager $activityManager,
        ActivityContextApiEntityManager $contextManager
    ) {
        $this->gridConfigurationHelper = $gridConfigurationHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->activityManager = $activityManager;
        $this->contextManager = $contextManager;
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
        $this->addColumnToData($result->getData(), 'id', self::COLUMN_NAME);
    }

    /**
     * Add context data to result rows for every entity id found
     *
     * @param array $rows
     * @param string $identifier
     * @param string $columnId
     *
     * @return array
     */
    protected function addColumnToData(array $rows, $identifier, $columnId)
    {
        return array_map(
            function (ResultRecord $item) use ($identifier, $columnId) {
                $id = $item->getValue($identifier);
                $data = $this->contextManager->getActivityContext($this->entityClassName, $id);
                $item->addData([$columnId => $data]);

                return $item;
            },
            $rows
        );
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
