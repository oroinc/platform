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
    /** @var ActionFactoryInterface */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    private OptionsResolver $optionsResolver;
    private OperationEventDispatcher $eventDispatcher;
    private ServiceProviderInterface $operationServiceLocator;

    public function __construct(
        ActionFactoryInterface $actionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        OptionsResolver $optionsResolver
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->optionsResolver = $optionsResolver;
    }

    public function setEventDispatcher(OperationEventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setOperationServiceLocator(ServiceProviderInterface $serviceLocator): void
    {
        $this->operationServiceLocator = $serviceLocator;
    }

    /**
     * @param string $name
     * @param array $configuration
     * @return Operation
     */
    public function createOperation($name, array $configuration)
    {
        $operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->optionsResolver,
            $this->assembleDefinition($name, $configuration)
        );
        $operation->setEventDispatcher($this->eventDispatcher);

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

    /**
     * @param string $operationName
     * @param array $options
     * @return OperationDefinition
     */
    protected function assembleDefinition($operationName, array $options)
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

    /**
     * @param OperationDefinition $operationDefinition
     * @param mixed $aclResource
     */
    protected function addAclPrecondition(OperationDefinition $operationDefinition, $aclResource)
    {
        if (!$aclResource) {
            return;
        }

        $aclDefinition = ['@acl_granted' => $aclResource];
        $definition = $operationDefinition->getConditions(OperationDefinition::PRECONDITIONS);
        if ($definition) {
            $newDefinition['@and'][] = $definition;
        }
        $newDefinition['@and'][] = $aclDefinition;

        $operationDefinition->setConditions(OperationDefinition::PRECONDITIONS, $newDefinition);
    }

    protected function addFeaturePrecondition(OperationDefinition $operationDefinition)
    {
        $featureResourceDefinition = [
            '@feature_resource_enabled' => [
                'resource' => $operationDefinition->getName(),
                'resource_type' => 'operations'
            ]
        ];
        $definition = $operationDefinition->getConditions(OperationDefinition::PRECONDITIONS);
        if ($definition) {
            $newDefinition['@and'][] = $definition;
        }
        $newDefinition['@and'][] = $featureResourceDefinition;

        $operationDefinition->setConditions(OperationDefinition::PRECONDITIONS, $newDefinition);
    }
}
