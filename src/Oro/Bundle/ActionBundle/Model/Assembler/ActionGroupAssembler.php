<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroupAssembler extends AbstractAssembler
{
    /** @var ActionFactory */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var ParameterAssembler */
    private $parameterAssembler;

    /**@var ActionGroup\ParametersResolver */
    private $parametersResolver;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param ParameterAssembler $parameterAssembler
     * @param ActionGroup\ParametersResolver $parametersResolver
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ActionGroup\ParametersResolver $parametersResolver
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->parametersResolver = $parametersResolver;
    }

    /**
     * @param array $configuration
     * @return ActionGroup[]
     */
    public function assemble(array $configuration)
    {
        $actionGroups = [];

        foreach ($configuration as $actionGroupName => $options) {
            $actionGroups[$actionGroupName] = new ActionGroup(
                $this->actionFactory,
                $this->conditionFactory,
                $this->parameterAssembler,
                $this->parametersResolver,
                $this->assembleDefinition($actionGroupName, $options)
            );
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

    /**
     * @param ActionGroupDefinition $definition
     * @param array $options
     */
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
