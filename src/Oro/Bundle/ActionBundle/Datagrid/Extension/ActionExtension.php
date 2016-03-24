<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;

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

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /** @var array */
    protected $actionConfiguration = [];

    /** @var array */
    protected $datagridContext = [];

    /** @var array|Action[] */
    protected $actions = [];

    /** @var array */
    protected $groups;

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param MassActionProviderRegistry $providerRegistry
     * @param OptionsHelper $optionsHelper
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        MassActionProviderRegistry $providerRegistry,
        OptionsHelper $optionsHelper
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->providerRegistry = $providerRegistry;
        $this->optionsHelper = $optionsHelper;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->actions = $this->getActions(
            $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []),
            $this->datagridContext
        );

        if (0 === count($this->actions)) {
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
     * @return Action[]
     */
    protected function getActions(array $actionsConfig, array $datagridContext)
    {
        $result = [];

        $actions = $this->actionManager->getActions($datagridContext, false);

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
        $actionsNew = [];
        foreach ($this->actions as $actionName => $action) {
            $actionsNew[$actionName] = $this->getRowActionsConfig($action, $record->getValue('id'));
        }

        $result = array_filter($this->getParentRowConfiguration($record, $config), function ($item) {
            return $item === false || is_array($item);
        });

        return array_merge($result, $actionsNew);
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
     * @param Action $action
     * @param mixed $entityId
     * @return boolean
     */
    protected function getRowActionsConfig(Action $action, $entityId)
    {
        $context = [
            'entityId' => $entityId,
            'entityClass' => $this->datagridContext['entityClass'],
            'datagrid' => $this->datagridContext['datagrid'],
        ];

        $actionData = $this->contextHelper->getActionData($context);

        if (!$action->isAvailable($actionData)) {
            return false;
        }

        $frontendOptions = $this->optionsHelper->getFrontendOptions($action, $context);

        return $frontendOptions['options'];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []);

        foreach ($this->actions as $actionName => $action) {
            $actionsConfig[$actionName] = $this->getRowsActionsConfig($action, $actionName);
        }

        $config->offsetSet(DatagridActionExtension::ACTION_KEY, $actionsConfig);
    }

    /**
     * @param Action $action
     * @param string $actionName
     * @return array
     */
    protected function getRowsActionsConfig(Action $action, $actionName)
    {
        $buttonOptions = $action->getDefinition()->getButtonOptions();
        $icon = !empty($buttonOptions['icon']) ? str_ireplace('icon-', '', $buttonOptions['icon']) : 'edit';

        return [
            'type' => 'action-widget',
            'label' => $action->getDefinition()->getLabel(),
            'rowAction' => false,
            'link' => '#',
            'icon' => $icon,
            'options' => [
                'actionName' => $actionName,
            ]
        ];
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
            ContextHelper::ENTITY_CLASS_PARAM => $entityClass ?: $config->offsetGetByPath('[entity_name]'),
            ContextHelper::DATAGRID_PARAM => $config->offsetGetByPath('[name]'),
            ContextHelper::GROUP_PARAM => $this->groups,
        ];
    }
}
