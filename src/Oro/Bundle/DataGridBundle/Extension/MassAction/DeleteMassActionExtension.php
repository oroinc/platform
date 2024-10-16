<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * Oro DataGrid Delete Mass Action Extension
 */
class DeleteMassActionExtension extends AbstractExtension
{
    public const ACTION_KEY         = 'actions';
    public const MASS_ACTION_KEY    = 'mass_actions';
    public const ACTION_TYPE_KEY    = 'type';
    public const ACTION_TYPE_DELETE = 'delete';

    public const MASS_ACTION_OPTION_PATH = '[mass_actions][delete]';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var array */
    protected $actions = [];

    /** @var string|null */
    protected $entityClassName;

    public function __construct(DoctrineHelper $doctrineHelper, EntityClassResolver $entityClassResolver)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassResolver = $entityClassResolver;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!$config->isOrmDatasource() || !parent::isApplicable($config)) {
            return false;
        }

        $options = $config->offsetGetByPath(self::MASS_ACTION_OPTION_PATH, []);

        return
            !($options[PropertyInterface::DISABLED_KEY] ?? false) &&
            // Checks if mass delete action does not exists
            !$this->isDeleteActionExists($config, static::MASS_ACTION_KEY) &&
            // Checks if delete action exists
            $this->isDeleteActionExists($config, static::ACTION_KEY) &&
            $this->isApplicableForEntity($config);
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
            $config->getOrmQuery()->getRootAlias();
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
            $actionType = $action[static::ACTION_TYPE_KEY] ?? '';
            if ($actionType === static::ACTION_TYPE_DELETE) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
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
            'icon'            => 'trash-o',
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
            $this->entityClassName = $config->getOrmQuery()->getRootEntity($this->entityClassResolver, true);
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
        $rootAlias  = $config->getOrmQuery()->getRootAlias();

        return sprintf('%s.%s', $rootAlias, $identifier);
    }

    #[\Override]
    public function getPriority()
    {
        // should be applied before mass action extension
        return 210;
    }
}
