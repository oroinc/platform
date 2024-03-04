<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\ActionGroupServiceAdapter;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ActionGroupAssembler extends AbstractAssembler
{
    private ActionFactoryInterface $actionFactory;
    private ConditionFactory $conditionFactory;
    private ParameterAssembler $parameterAssembler;
    private ActionGroup\ParametersResolver $parametersResolver;
    private ServiceProviderInterface $actionGroupServiceLocator;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ActionGroup\ParametersResolver $parametersResolver,
        ServiceProviderInterface $actionGroupServiceLocator
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->parametersResolver = $parametersResolver;
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
                    $this->getOption($options, 'method', 'execute')
                );
            } else {
                $actionGroups[$actionGroupName] = new ActionGroup(
                    $this->actionFactory,
                    $this->conditionFactory,
                    $this->parameterAssembler,
                    $this->parametersResolver,
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

        $definition
            ->setName($actionGroupName)
            ->setConditions($this->getOption($options, 'conditions', []))
            ->setActions($this->getOption($options, 'actions', []))
            ->setParameters($this->getOption($options, 'parameters', []));

        $this->addConditions($definition, $options);

        return $definition;
    }

    protected function addConditions(ActionGroupDefinition $definition, array $options)
    {
        $conditions = $this->getAclConditions($this->getOption($options, 'acl_resource'));

        if ($currentConditions = $definition->getConditions()) {
            $conditions = array_merge($conditions, [$currentConditions]);
        }

        $definition->setConditions($conditions ? ['@and' => $conditions] : []);
    }

    /**
     * @param string|array|null $aclResource
     * @return array
     */
    protected function getAclConditions($aclResource)
    {
        if (!$aclResource) {
            return [];
        }

        return [['@acl_granted' => $aclResource]];
    }
}
