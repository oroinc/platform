<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
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
     * @return ActionGroup[]
     */
    public function assemble(array $configuration)
    {
        $actionGroups = [];

        foreach ($configuration as $actionGroupName => $options) {
            $actionGroups[$actionGroupName] = new ActionGroup(
                $this->actionFactory,
                $this->conditionFactory,
                $this->argumentAssembler,
                $this->doctrineHelper,
                $this->assembleDefinition($actionGroupName, $options)
            );
        }

        return $actionGroups;
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

        $this->addConditions($definition, $options);

        return $definition;
    }

    /**
     * @param ActionGroupDefinition $definition
     * @param array $options
     */
    protected function addConditions(ActionGroupDefinition $definition, array $options)
    {
        $conditions = array_merge(
            $this->getAclConditions($this->getOption($options, 'acl_resource')),
            $this->getArgumentsConditions($this->getOption($options, 'arguments', []))
        );

        if ($currentConditions = $definition->getConditions()) {
            $conditions = array_merge($conditions, [$currentConditions]);
        }

        $definition->setConditions($conditions ? ['@and' => $conditions] : []);
    }

    /**
     * @param array $arguments
     * @return array
     */
    protected function getArgumentsConditions(array $arguments)
    {
        if (!$arguments) {
            return [];
        }

        $conditions = [];

        foreach ($arguments as $name => $argument) {
            $action = '$' . $name;
            $message = !empty($argument['message']) ? $argument['message'] . ': ' : '';

            if (!empty($argument['required'])) {
                $conditions[] = [
                    '@has_value' => [
                        'parameters' => [$action],
                        'message' => sprintf('%s%s is required', $message, $action),
                    ]
                ];
            }

            if (!empty($argument['type'])) {
                $conditions[] = [
                    '@type' => [
                        'parameters' => [$action, $argument['type']],
                        'message' => sprintf(
                            '%s%s must be of type "{{ type }}", "{{ value }}" given',
                            $message,
                            $action
                        ),
                    ]
                ];
            }
        }

        return $conditions;
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
