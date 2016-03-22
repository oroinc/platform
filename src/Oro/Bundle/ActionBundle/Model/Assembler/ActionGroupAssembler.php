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

    /** @var ParameterAssembler */
    private $parameterAssembler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param ParameterAssembler $parameterAssembler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
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
                $this->parameterAssembler,
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
        $conditions = array_merge(
            $this->getAclConditions($this->getOption($options, 'acl_resource')),
            $this->getParametersConditions($this->getOption($options, 'parameters', []))
        );

        if ($currentConditions = $definition->getConditions()) {
            $conditions = array_merge($conditions, [$currentConditions]);
        }

        $definition->setConditions($conditions ? ['@and' => $conditions] : []);
    }

    /**
     * @param array $parameters
     * @return array
     */
    protected function getParametersConditions(array $parameters)
    {
        if (!$parameters) {
            return [];
        }

        $conditions = [];

        foreach ($parameters as $name => $parameter) {
            $action = '$.' . $name;
            $message = !empty($parameter['message']) ? $parameter['message'] . ': ' : '';

            if (!empty($parameter['required'])) {
                $conditions[] = [
                    '@has_value' => [
                        'parameters' => [$action],
                        'message' => sprintf('%s%s is required', $message, $action),
                    ]
                ];
            }

            if (!empty($parameter['type'])) {
                $conditions[] = [
                    '@type' => [
                        'parameters' => [$action, $parameter['type']],
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
