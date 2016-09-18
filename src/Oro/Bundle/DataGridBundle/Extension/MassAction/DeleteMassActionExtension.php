<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DeleteMassActionExtension extends AbstractExtension
{
    const ACTION_KEY         = 'actions';
    const MASS_ACTION_KEY    = 'mass_actions';
    const ACTION_TYPE_KEY    = 'type';
    const ACTION_TYPE_DELETE = 'delete';

    const MASS_ACTION_OPTION_PATH = '[options][mass_actions][delete]';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /** @var array */
    protected $actions = [];

    /** @var string|null */
    protected $entityClassName;

    /**
     * @param DoctrineHelper          $doctrineHelper
     * @param GridConfigurationHelper $gridConfigurationHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, GridConfigurationHelper $gridConfigurationHelper)
    {
        $this->doctrineHelper          = $doctrineHelper;
        $this->gridConfigurationHelper = $gridConfigurationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if ($config->getDatasourceType() !== OrmDatasource::TYPE) {
            return false;
        }

        // validate configuration and fill default values
        $options = $this->validateConfiguration(
            new DeleteMassActionConfiguration(),
            ['delete' => $config->offsetGetByPath(self::MASS_ACTION_OPTION_PATH, true)]
        );

        return
            // Checks if mass delete action does not exists
            !$this->isDeleteActionExists($config, static::MASS_ACTION_KEY) &&
            // Checks if delete action exists
            $this->isDeleteActionExists($config, static::ACTION_KEY) &&
            $this->isApplicableForEntity($config) &&
            $options['enabled'];
    }

    /**
     * Checks if we can apply mass delete action for the entity:
     *  - extract entity class
     *  - extract entity single identifier
     *  - extract alias for this entity
     *
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function isApplicableForEntity(DatagridConfiguration $config)
    {
        $entity = $this->getEntity($config);

        return
            $entity &&
            $this->doctrineHelper->getSingleEntityIdentifierFieldName($entity, false) &&
            $this->gridConfigurationHelper->getEntityRootAlias($config);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $key
     *
     * @return bool
     */
    protected function isDeleteActionExists(DatagridConfiguration $config, $key)
    {
        $actions = $config->offsetGetOr($key, []);
        foreach ($actions as $action) {
            if ($action[static::ACTION_TYPE_KEY] === static::ACTION_TYPE_DELETE) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr(static::MASS_ACTION_KEY, []);

        $actions['delete'] = $this->getMassDeleteActionConfig($config);

        $config->offsetSet(static::MASS_ACTION_KEY, $actions);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getMassDeleteActionConfig(DatagridConfiguration $config)
    {
        $entity = $this->getEntity($config);

        return [
            'type'            => 'delete',
            'icon'            => 'trash',
            'label'           => 'oro.grid.action.delete',
            'entity_name'     => $entity,
            'data_identifier' => $this->getDataIdentifier($config),
            'acl_resource'    => sprintf('DELETE;entity:%s', $entity),
        ];
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        if ($this->entityClassName === null) {
            $this->entityClassName = $this->gridConfigurationHelper->getEntity($config);
        }

        return $this->entityClassName;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string
     */
    protected function getDataIdentifier(DatagridConfiguration $config)
    {
        $entity     = $this->getEntity($config);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entity);
        $rootAlias  = $this->gridConfigurationHelper->getEntityRootAlias($config);

        return sprintf('%s.%s', $rootAlias, $identifier);
    }


    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be applied before mass action extension
        return 210;
    }
}
