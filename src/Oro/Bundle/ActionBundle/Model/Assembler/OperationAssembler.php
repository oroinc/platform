<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Event\OperationEventDispatcher;
use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Factory for Operation class
 */
class OperationAssembler extends AbstractAssembler
{
    public function __construct(
        private ActionFactoryInterface $actionFactory,
        private ConditionFactory $conditionFactory,
        private AttributeAssembler $attributeAssembler,
        private FormOptionsAssembler $formOptionsAssembler,
        private OptionsResolver $optionsResolver,
        private OperationEventDispatcher $eventDispatcher,
        private ServiceProviderInterface $operationServiceLocator
    ) {
    }

    public function createOperation(string $name, array $configuration): Operation
    {
        $operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->optionsResolver,
            $this->eventDispatcher,
            $this->assembleDefinition($name, $configuration)
        );

        $operationServiceName = $this->getOption($configuration, 'service', null);
        if ($operationServiceName) {
            $operationService = $this->operationServiceLocator->get($operationServiceName);
            if ($operationService instanceof ResetInterface) {
                $operationService->reset();
            }
            $operation->setOperationService($operationService);
        }

        return $operation;
    }

    protected function assembleDefinition(string $operationName, array $options): OperationDefinition
    {
        $this->assertOptions($options, ['label'], $operationName);
        $operationDefinition = new OperationDefinition();

        $operationDefinition
            ->setName($operationName)
            ->setLabel($this->getOption($options, 'label'))
            ->setSubstituteOperation($this->getOption($options, 'substitute_operation', null))
            ->setEnabled($this->getOption($options, 'enabled', true))
            ->setPageReload($this->getOption($options, 'page_reload', true))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setFormType($this->getOption($options, 'form_type', OperationType::class))
            ->setButtonOptions($this->getOption($options, 'button_options', []))
            ->setFrontendOptions($this->getOption($options, 'frontend_options', []))
            ->setDatagridOptions($this->getOption($options, 'datagrid_options', []))
            ->setAttributes($this->getOption($options, 'attributes', []))
            ->setFormOptions($this->getOption($options, 'form_options', []))
            ->setActionGroups($this->getOption($options, 'action_groups', []))
            ->setAclResource($this->getOption($options, 'acl_resource'));

        $this->initializeLogicComponents($options, $operationDefinition);

        return $operationDefinition;
    }

    private function initializeLogicComponents(array $options, OperationDefinition $operationDefinition): void
    {
        if ($this->getOption($options, 'service')) {
            $operationDefinition->setActions(
                OperationDefinition::FORM_INIT,
                $this->getOption($options, OperationDefinition::FORM_INIT, [])
            );

            return;
        }

        foreach (OperationDefinition::getAllowedConditions() as $name) {
            $operationDefinition->setConditions($name, $this->getOption($options, $name, []));
        }

        foreach (OperationDefinition::getAllowedActions() as $name) {
            $operationDefinition->setActions($name, $this->getOption($options, $name, []));
        }
    }
}
