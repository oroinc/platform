<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroupAssembler extends AbstractAssembler
{
    /** @var ActionFactory */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var ArgumentAssembler */
    private $argumentAssembler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param ArgumentAssembler $argumentAssembler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        ArgumentAssembler $argumentAssembler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->argumentAssembler = $argumentAssembler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     * @return Operation[]
     */
    public function assemble(array $configuration)
    {
        $actions = [];

        foreach ($configuration as $actionGroupName => $options) {
            $actions[$actionGroupName] = new ActionGroup(
                $this->actionFactory,
                $this->conditionFactory,
                $this->argumentAssembler,
                $this->assembleDefinition($actionGroupName, $options)
            );
        }

        return $actions;
    }

    /**
     * @param string $actionName
     * @param array $options
     * @return ActionGroupDefinition
     */
    protected function assembleDefinition($actionName, array $options)
    {
        $definition = new ActionGroupDefinition();

        $definition
            ->setName($actionName)
            ->setConditions($this->getOption($options, 'conditions', []))
            ->setActions($this->getOption($options, 'actions', []))
            ->setArguments($this->getOption($options, 'arguments', []));

        $this->addAclCondition($definition, $this->getOption($options, 'acl_resource'));

        return $definition;
    }

    /**
     * @param ActionGroupDefinition $definition
     * @param string|array|null $aclResource
     */
    protected function addAclCondition(ActionGroupDefinition $definition, $aclResource)
    {
        if (!$aclResource) {
            return;
        }

        $newConditions = ['@and' => [['@acl_granted' => $aclResource]]];
        if ($conditions = $definition->getConditions()) {
            $newConditions['@and'][] = $conditions;
        }

        $definition->setConditions($newConditions);
    }
}
