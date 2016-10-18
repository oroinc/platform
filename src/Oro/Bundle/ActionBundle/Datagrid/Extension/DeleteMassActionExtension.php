<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionExtension as BaseDeleteMassActionExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DeleteMassActionExtension extends BaseDeleteMassActionExtension
{
    const OPERATION_NAME = 'DELETE';

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

        return $this->operationManager->hasOperation(self::OPERATION_NAME, $this->getDatagridContext($config));
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
            ContextHelper::DATAGRID_PARAM => $config->getName(),
            ContextHelper::GROUP_PARAM => $this->groups,
        ];
    }
}
