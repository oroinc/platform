<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Form\Type\ActionType;
use Oro\Component\Action\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler extends AbstractAssembler
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler
    ) {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
    }

    /**
     * @param array $configuration
     * @return Operation[]
     */
    public function assemble(array $configuration)
    {
        $actions = [];

        foreach ($configuration as $actionName => $options) {
            $actions[$actionName] = new Operation(
                $this->functionFactory,
                $this->conditionFactory,
                $this->attributeAssembler,
                $this->formOptionsAssembler,
                $this->assembleDefinition($actionName, $options)
            );
        }

        return $actions;
    }

    /**
     * @param string $actionName
     * @param array $options
     * @return OperationDefinition
     */
    protected function assembleDefinition($actionName, array $options)
    {
        $this->assertOptions($options, ['label'], $actionName);
        $operationDefinition = new OperationDefinition();

        $operationDefinition
            ->setName($actionName)
            ->setLabel($this->getOption($options, 'label'))
            ->setEntities($this->getOption($options, 'entities', []))
            ->setDatagrids($this->getOption($options, 'datagrids', []))
            ->setRoutes($this->getOption($options, 'routes', []))
            ->setApplications($this->getOption($options, 'applications', []))
            ->setEnabled($this->getOption($options, 'enabled', true))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setFormType($this->getOption($options, 'form_type', ActionType::NAME))
            ->setButtonOptions($this->getOption($options, 'button_options', []))
            ->setFrontendOptions($this->getOption($options, 'frontend_options', []))
            ->setDatagridOptions($this->getOption($options, 'datagrid_options', []))
            ->setAttributes($this->getOption($options, 'attributes', []))
            ->setFormOptions($this->getOption($options, 'form_options', []))
            ->setPreconditions($this->getOption($options, 'preconditions', []));

        foreach (OperationDefinition::getAllowedActions() as $name) {
            $operationDefinition->setActions($name, $this->getOption($options, $name, []));
        }

        $this->addAclPrecondition($operationDefinition, $this->getOption($options, 'acl_resource'));

        return $operationDefinition;
    }

    /**
     * @param OperationDefinition $operationDefinition
     * @param mixed $aclResource
     */
    protected function addAclPrecondition(OperationDefinition $operationDefinition, $aclResource)
    {
        if (!$aclResource) {
            return;
        }

        $definition = $operationDefinition->getPreconditions();

        $newDefinition = ['@and' => [['@acl_granted' => $aclResource]]];
        if ($definition) {
            $newDefinition['@and'][] = $definition;
        }

        $operationDefinition->setPreconditions($newDefinition);
    }
}
