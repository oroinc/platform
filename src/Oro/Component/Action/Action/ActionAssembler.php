<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\Action\Model\AbstractAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler extends AbstractAssembler
{
    const PARAMETERS_KEY = 'parameters';
    const BREAK_ON_FAILURE_KEY = 'break_on_failure';
    const ACTIONS_KEY = 'actions';
    const CONDITIONS_KEY = 'conditions';

    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(ActionFactory $actionFactory, ConditionFactory $conditionFactory)
    {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory  = $conditionFactory;
    }

    /**
     * Allowed formats:
     *
     * array(
     *     'conditions' => array(<condition_data>),
     *     'actions' => array(
     *         array(<first_action_data>),
     *         array(<second_action_data>),
     *         ...
     *     )
     * )
     *
     * or
     *
     * array(
     *     array(<first_action_data>),
     *     array(<second_action_data>),
     *     ...
     * )
     *
     * @param array $configuration
     * @return ActionInterface
     */
    public function assemble(array $configuration)
    {
        /** @var TreeExecutor $treeAction */
        $treeAction = $this->actionFactory->create(
            TreeExecutor::ALIAS,
            array(),
            $this->createConfigurableCondition($configuration)
        );

        $actionsConfiguration = $this->getOption($configuration, self::ACTIONS_KEY, $configuration);
        foreach ($actionsConfiguration as $actionConfiguration) {
            if ($this->isService($actionConfiguration)) {
                $options = (array)$this->getEntityParameters($actionConfiguration);
                $breakOnFailure = $this->getOption($options, self::BREAK_ON_FAILURE_KEY, true);

                $actionType = $this->getEntityType($actionConfiguration);
                $serviceName = $this->getServiceName($actionType);

                if ($serviceName == TreeExecutor::ALIAS) {
                    $action = $this->assemble($options);
                } else {
                    $actionParameters = $this->getOption($options, self::PARAMETERS_KEY, $options);
                    $passedActionParameters = $this->passConfiguration($actionParameters);
                    $action = $this->actionFactory->create(
                        $serviceName,
                        $passedActionParameters,
                        $this->createConfigurableCondition($options)
                    );
                }

                $treeAction->addAction($action, $breakOnFailure);
            }
        }

        return $treeAction;
    }

    /**
     * @param array $conditionConfiguration
     * @return null|ConfigurableCondition
     */
    protected function createConfigurableCondition(array $conditionConfiguration)
    {
        $condition = null;
        $conditionConfiguration = $this->getOption($conditionConfiguration, self::CONDITIONS_KEY, null);
        if ($conditionConfiguration) {
            $condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $conditionConfiguration);
        }

        return $condition;
    }
}
