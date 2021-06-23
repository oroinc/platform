<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Util\PropertyPathSecurityHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractWorkflowAttributesTypeTestCase extends FormIntegrationTestCase
{
    protected function createWorkflow(
        string $workflowName,
        array $attributes = [],
        array $steps = [],
        string $relatedEntity = null
    ): Workflow {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);

        $workflow = new Workflow($doctrineHelper, $aclManager, $restrictionManager);

        foreach ($attributes as $name => $attribute) {
            $workflow->getAttributeManager()->getAttributes()->set($name, $attribute);
        }

        $workflow->getStepManager()->setSteps($steps);

        $definition = new WorkflowDefinition();
        $definition->setName($workflowName);
        $definition->setRelatedEntity($relatedEntity);
        $workflow->setDefinition($definition);

        return $workflow;
    }

    protected function createWorkflowData(array $data = []): WorkflowData
    {
        $result = new WorkflowData();
        foreach ($data as $name => $value) {
            $result->set($name, $value);
        }

        return $result;
    }

    protected function createAttribute(
        string $name = null,
        string $type = null,
        string $label = null,
        string $propertyPath = null
    ): Attribute {
        $result = new Attribute();
        $result->setName($name);
        $result->setType($type);
        $result->setLabel($label);
        $result->setPropertyPath($propertyPath);

        return $result;
    }

    protected function createStep(string $name = null): Step
    {
        $result = new Step();
        $result->setName($name);

        return $result;
    }

    protected function createWorkflowItem(Workflow $workflow, WorkflowStep $currentStep = null): WorkflowItem
    {
        $result = new WorkflowItem();
        $result->setCurrentStep($currentStep);
        $result->setWorkflowName($workflow->getName());

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function createWorkflowAttributesType(
        WorkflowRegistry $workflowRegistry = null,
        AttributeGuesser $attributeGuesser = null,
        DefaultValuesListener $defaultValuesListener = null,
        FormInitListener $formInitListener = null,
        RequiredAttributesListener $requiredAttributesListener = null,
        EventDispatcherInterface $dispatcher = null,
        PropertyPathSecurityHelper $propertyPathSecurityHelper = null,
        TranslatorInterface $translator = null
    ): WorkflowAttributesType {
        if (!$workflowRegistry) {
            $workflowRegistry = $this->createMock(WorkflowRegistry::class);
        }
        if (!$attributeGuesser) {
            $attributeGuesser = $this->createMock(AttributeGuesser::class);
        }
        if (!$defaultValuesListener) {
            $defaultValuesListener = $this->getMockBuilder(DefaultValuesListener::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['initialize', 'setDefaultValues'])
                ->getMock();
        }
        if (!$formInitListener) {
            $formInitListener = $this->getMockBuilder(FormInitListener::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['initialize', 'executeInitAction'])
                ->getMock();
        }
        if (!$requiredAttributesListener) {
            $requiredAttributesListener = $this->getMockBuilder(RequiredAttributesListener::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['initialize', 'onPreSetData', 'onSubmit'])
                ->getMock();
        }
        if (!$dispatcher) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }
        if (!$propertyPathSecurityHelper) {
            $propertyPathSecurityHelper = $this->createMock(PropertyPathSecurityHelper::class);
        }
        if (!$translator) {
            $translator = $this->createMock(TranslatorInterface::class);
        }

        return new WorkflowAttributesType(
            $workflowRegistry,
            $attributeGuesser,
            $defaultValuesListener,
            $formInitListener,
            $requiredAttributesListener,
            new ContextAccessor(),
            $dispatcher,
            $propertyPathSecurityHelper,
            $translator
        );
    }
}
