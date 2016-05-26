<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionExtension as BaseDeleteMassActionExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DeleteMassActionExtension extends BaseDeleteMassActionExtension
{
    /** @var OperationManager */
    protected $operationManager;

    /** @var array */
    protected $groups;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param GridConfigurationHelper $gridConfigurationHelper
     * @param OperationManager $operationManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        GridConfigurationHelper $gridConfigurationHelper,
        OperationManager $operationManager
    ) {
        parent::__construct($doctrineHelper, $gridConfigurationHelper);

        $this->operationManager = $operationManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function isDeleteActionExists(DatagridConfiguration $config, $key)
    {
        if ($key !== static::ACTION_KEY) {
            return parent::isDeleteActionExists($config, $key);
        }

        $datagridContext = $this->getDatagridContext($config);
        $operations = $this->getOperations(
            $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []),
            $datagridContext
        );

        foreach ($operations as $operation) {
            if (strtolower($operation->getName()) === static::ACTION_TYPE_DELETE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets operations from registry if they not already exist in datagrid config as actions
     * @param array $datagridActionsConfig
     * @param array $datagridContext
     * @return Operation[]
     */
    protected function getOperations(array $datagridActionsConfig, array $datagridContext)
    {
        $result = [];

        $operations = $this->operationManager->getOperations($datagridContext);

        foreach ($operations as $operationName => $action) {
            $operationName = strtolower($operationName);
            if (array_key_exists($operationName, $datagridActionsConfig)) {
                $result[$operationName] = $action;
            }
        }

        return $result;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        return [
            ContextHelper::ENTITY_CLASS_PARAM => $this->gridConfigurationHelper->getEntity($config),
            ContextHelper::DATAGRID_PARAM => $config->offsetGetByPath('[name]'),
            ContextHelper::GROUP_PARAM => $this->groups,
        ];
    }
}
