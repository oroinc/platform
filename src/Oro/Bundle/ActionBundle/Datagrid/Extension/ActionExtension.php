<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;

class ActionExtension extends AbstractExtension
{
    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelper */
    protected $applicationHelper;

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var array */
    protected $actionConfiguration = [];

    /** @var array */
    protected $datagridContext = [];

    /** @var array|Action[] */
    protected $actions = [];

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationHelper
     * @param MassActionProviderRegistry $providerRegistry
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationHelper,
        MassActionProviderRegistry $providerRegistry
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationHelper = $applicationHelper;
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->actions = $this->actionManager->getActions($this->datagridContext, false);

        if (0 === count($this->actions)) {
            return false;
        }

        $this->processActionsConfig($config);
        $this->processMassActionsConfig($config);
        $this->actionConfiguration = $config->offsetGetOr(DatagridActionExtension::ACTION_CONFIGURATION_KEY, []);
        $config->offsetSet(DatagridActionExtension::ACTION_CONFIGURATION_KEY, [$this, 'getActionsPermissions']);

        return true;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getActionsPermissions(ResultRecordInterface $record)
    {
        $actionsOld = [];
        // process own permissions of the datagrid
        if ($this->actionConfiguration && is_callable($this->actionConfiguration)) {
            $actionsOld = call_user_func($this->actionConfiguration, $record);
            $actionsOld = is_array($actionsOld) ? $actionsOld : [];
        };

        $actionData = $this->contextHelper->getActionData([
            'entityId' => $record->getValue('id'),
            'entityClass' => $this->datagridContext['entityClass'],
        ]);

        $actionsNew = [];
        foreach ($this->actions as $action) {
            $actionsNew[$action->getName()] = $action->isAvailable($actionData);
        }

        return array_merge($actionsOld, $actionsNew);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr('actions', []);

        foreach ($this->actions as $action) {
            if (!array_key_exists($action->getName(), $actionsConfig)) {
                $buttonOptions = $action->getDefinition()->getButtonOptions();
                $frontendOptions = $action->getDefinition()->getFrontendOptions();
                $icon = !empty($buttonOptions['icon']) ? str_ireplace('icon-', '', $buttonOptions['icon']) : 'edit';
                $confirmation = !empty($frontendOptions['confirmation']) ? $frontendOptions['confirmation'] : '';
                $actionsConfig[$action->getName()] = [
                    'type' => 'action-widget',
                    'label' => $action->getDefinition()->getLabel(),
                    'rowAction' => false,
                    'link' => '#',
                    'icon' => $icon,
                    'options' => [
                        'actionName' => $action->getName(),
                        'entityClass' => $this->datagridContext['entityClass'],
                        'datagrid' => $this->datagridContext['datagrid'],
                        'confirmation' => $confirmation,
                        'showDialog' => $action->hasForm(),
                        'executionRoute' => $this->applicationHelper->getExecutionRoute(),
                        'dialogRoute' => $this->applicationHelper->getDialogRoute(),
                        'dialogOptions' => [
                            'title' => $action->getDefinition()->getLabel(),
                            'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
                        ]
                    ]
                ];
            }

        }

        $config->offsetSet('actions', $actionsConfig);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processMassActionsConfig(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->actions as $action) {
            $datagridOptions = $action->getDefinition()->getDatagridOptions();

            if (!empty($datagridOptions['mass_action_provider'])) {
                $provider = $this->providerRegistry->getProvider($datagridOptions['mass_action_provider']);

                if ($provider) {
                    foreach ($provider->getActions() as $name => $massAction) {
                        $actions[$action->getName() . $name] = $massAction;
                    }
                }
            } elseif (!empty($datagridOptions['mass_action'])) {
                $actions[$action->getName()] = array_merge(
                    [
                        'label' => $action->getDefinition()->getLabel()
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
        ];
    }
}
