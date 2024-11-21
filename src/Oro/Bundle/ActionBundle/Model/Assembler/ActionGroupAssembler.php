<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\ActionGroupServiceAdapter;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * ActionGroup service assembler. Assembles action group based on a configuration.
 */
class ActionGroupAssembler extends AbstractAssembler
{
    private ActionFactoryInterface $actionFactory;
    private ConditionFactory $conditionFactory;
    private ParameterAssembler $parameterAssembler;
    private ActionGroup\ParametersResolver $parametersResolver;
    private ActionGroupEventDispatcher $eventDispatcher;
    private ServiceProviderInterface $actionGroupServiceLocator;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ActionGroup\ParametersResolver $parametersResolver,
        ActionGroupEventDispatcher $eventDispatcher,
        ServiceProviderInterface $actionGroupServiceLocator
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->parametersResolver = $parametersResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->actionGroupServiceLocator = $actionGroupServiceLocator;
    }

    /**
     * @param array $configuration
     * @return ActionGroup[]
     */
    public function assemble(array $configuration)
    {
        $actionGroups = [];

        foreach ($configuration as $actionGroupName => $options) {
            $serviceName = $this->getOption($options, 'service', null);
            if ($serviceName) {
                $actionGroups[$actionGroupName] = new ActionGroupServiceAdapter(
                    $this->parametersResolver,
                    $this->actionGroupServiceLocator->get($serviceName),
                    $this->eventDispatcher,
                    $this->getOption($options, 'method', 'execute'),
                    $this->getOption($options, 'return_value_name'),
                    $this->getOption($options, 'parameters'),
                    $this->assembleDefinition($actionGroupName, $options)
                );
            } else {
                $actionGroups[$actionGroupName] = new ActionGroup(
                    $this->actionFactory,
                    $this->conditionFactory,
                    $this->parameterAssembler,
                    $this->parametersResolver,
                    $this->eventDispatcher,
                    $this->assembleDefinition($actionGroupName, $options)
                );
            }
        }

        return $actionGroups;
    }

    /**
     * @param string $actionGroupName
     * @param array $options
     * @return ActionGroupDefinition
     */
    protected function assembleDefinition($actionGroupName, array $options)
    {
        $definition = new ActionGroupDefinition();
        $definition->setName($actionGroupName);
        $definition->setAclResource($this->getOption($options, 'acl_resource'));

        if (!$this->getOption($options, 'service')) {
            $definition
                ->setConditions($this->getOption($options, 'conditions', []))
                ->setActions($this->getOption($options, 'actions', []))
                ->setParameters($this->getOption($options, 'parameters', []));
        }

        return $definition;
    }
}
