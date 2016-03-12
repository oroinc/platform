<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Model\Operation;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;

class OperationExtension extends AbstractExtension
{
    /** @var OperationManager */
    protected $operationManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var array */
    protected $actionConfiguration = [];

    /** @var array */
    protected $datagridContext = [];

    /** @var array|Operation[] */
    protected $operations = [];

    /** @var array  */
    protected $actionGroups;

    /**
     * @param OperationManager $operationManager
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationsHelper
     * @param MassActionProviderRegistry $providerRegistry
     */
    public function __construct(
        OperationManager $operationManager,
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationsHelper,
        MassActionProviderRegistry $providerRegistry
    ) {
        $this->operationManager = $operationManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * @param array $actionGroups
     */
    public function setActionGroups(array $actionGroups)
    {
        $this->actionGroups = $actionGroups;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->operations = $this->getOperations(
            $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []),
            $this->datagridContext
        );

        if (0 === count($this->operations)) {
            return false;
        }

        $this->processActionsConfig($config);
        $this->processMassActionsConfig($config);

        $this->actionConfiguration = $config->offsetGetOr(DatagridActionExtension::ACTION_CONFIGURATION_KEY, []);
        $config->offsetSet(DatagridActionExtension::ACTION_CONFIGURATION_KEY, [$this, 'getRowConfiguration']);

        return true;
    }

    /**
     * @param array $actionsConfig
     * @param array $datagridContext
     * @return Operation[]
     */
    protected function getOperations(array $actionsConfig, array $datagridContext)
    {
        $result = [];

        $actions = $this->operationManager->getOperations($datagridContext, false);

        foreach ($actions as $actionName => $action) {
            $actionName = strtolower($actionName);
            if (!array_key_exists($actionName, $actionsConfig)) {
                $result[$actionName] = $action;
            }
        }

        return $result;
    }

    /**
     * @param ResultRecordInterface $record
     * @param array $config
     *
     * @return array
     */
    public function getRowConfiguration(ResultRecordInterface $record, array $config)
    {
        $actionData = $this->contextHelper->getActionData([
            'entityId' => $record->getValue('id'),
            'entityClass' => $this->datagridContext['entityClass'],
            'datagrid' => $this->datagridContext['datagrid'],
        ]);

        $actionsNew = [];
        foreach ($this->operations as $operationName => $operation) {
            $actionsNew[$operationName] = $this->getRowActionsConfig($operation, $actionData);
        }

        return array_merge($actionsNew, $this->getParentRowConfiguration($record, $config));
    }

    /**
     * @param ResultRecordInterface $record
     * @param array $config
     * @return array
     */
    protected function getParentRowConfiguration(ResultRecordInterface $record, array $config)
    {
        if (empty($this->actionConfiguration)) {
            return [];
        }

        $rowActions = [];

        if (is_callable($this->actionConfiguration)) {
            $rowActions = call_user_func($this->actionConfiguration, $record, $config);
        } elseif (is_array($this->actionConfiguration)) {
            $rowActions = $this->actionConfiguration;
        }

        return is_array($rowActions) ? $rowActions : [];
    }

    /**
     * @param Operation $operation
     * @param ActionData $actionData
     * @return bool|array
     */
    protected function getRowActionsConfig(Operation $operation, ActionData $actionData)
    {
        if (!$operation->isAvailable($actionData)) {
            return false;
        }

        return [
            'translates' => $actionData->getScalarValues(),
        ];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []);

        foreach ($this->operations as $operationName => $operation) {
            $actionsConfig[$operationName] = $this->getRowsActionsConfig($operation);
        }

        $config->offsetSet(DatagridActionExtension::ACTION_KEY, $actionsConfig);
    }

    /**
     * @param Operation $operation
     * @return array
     */
    protected function getRowsActionsConfig(Operation $operation)
    {
        $buttonOptions = $operation->getDefinition()->getButtonOptions();
        $frontendOptions = $operation->getDefinition()->getFrontendOptions();
        $icon = !empty($buttonOptions['icon']) ? str_ireplace('icon-', '', $buttonOptions['icon']) : 'edit';
        $confirmation = !empty($frontendOptions['confirmation']) ? $frontendOptions['confirmation'] : '';

        return [
            'type' => 'action-widget',
            'label' => $operation->getDefinition()->getLabel(),
            'rowAction' => false,
            'link' => '#',
            'icon' => $icon,
            'options' => [
                'actionName' => $operation->getName(),
                'entityClass' => $this->datagridContext['entityClass'],
                'datagrid' => $this->datagridContext['datagrid'],
                'confirmation' => $confirmation,
                'hasDialog' => $operation->hasForm(),
                'showDialog' => !empty($frontendOptions['show_dialog']),
                'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                'dialogOptions' => [
                    'title' => $operation->getDefinition()->getLabel(),
                    'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
                ]
            ]
        ];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processMassActionsConfig(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->operations as $operation) {
            $datagridOptions = $operation->getDefinition()->getDatagridOptions();

            if (!empty($datagridOptions['mass_action_provider'])) {
                $provider = $this->providerRegistry->getProvider($datagridOptions['mass_action_provider']);

                if ($provider) {
                    foreach ($provider->getActions() as $name => $massAction) {
                        $actions[$operation->getName() . $name] = $massAction;
                    }
                }
            } elseif (!empty($datagridOptions['mass_action'])) {
                $actions[$operation->getName()] = array_merge(
                    [
                        'label' => $operation->getDefinition()->getLabel()
                    ],
                    $datagridOptions['mass_action']
                );
            }
        }

        $config->offsetSet('mass_actions', $actions);
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        $entityClass = $config->offsetGetByPath('[extended_entity_name]');

        return [
            'entityClass' => $entityClass ?: $config->offsetGetByPath('[entity_name]'),
            'datagrid' => $config->offsetGetByPath('[name]'),
            'group' => $this->actionGroups,
        ];
    }
}
