<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;

use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\InitActionsListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

use Oro\Component\Action\Model\ContextAccessor;

abstract class AbstractWorkflowAttributesTypeTestCase extends FormIntegrationTestCase
{
    /**
     * @param string $workflowName
     * @param array $attributes
     * @param array $steps
     * @param string|null $relatedEntity
     * @return Workflow
     */
    protected function createWorkflow(
        $workflowName,
        array $attributes = array(),
        array $steps = array(),
        $relatedEntity = null
    ) {
        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = new Workflow($aclManager, $restrictionManager);

        $workflow->setName($workflowName);

        foreach ($attributes as $name => $attribute) {
            $workflow->getAttributeManager()->getAttributes()->set($name, $attribute);
        }

        $workflow->getStepManager()->setSteps($steps);

        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity($relatedEntity);
        $workflow->setDefinition($definition);

        return $workflow;
    }

    /**
     * @param array $data
     * @return WorkflowData
     */
    protected function createWorkflowData(array $data = array())
    {
        $result = new WorkflowData();
        foreach ($data as $name => $value) {
            $result->set($name, $value);
        }
        return $result;
    }

    /**
     * @param string|null $name
     * @param string|null $type
     * @param string|null $label
     * @param string|null $propertyPath
     * @return Attribute
     */
    protected function createAttribute($name = null, $type = null, $label = null, $propertyPath = null)
    {
        $result = new Attribute();
        $result->setName($name);
        $result->setType($type);
        $result->setLabel($label);
        $result->setPropertyPath($propertyPath);
        return $result;
    }

    /**
     * @param string|null $name
     * @return Step
     */
    protected function createStep($name = null)
    {
        $result = new Step();
        $result->setName($name);
        return $result;
    }

    /**
     * @param Workflow $workflow
     * @param WorkflowStep $currentStep
     * @return WorkflowItem
     */
    protected function createWorkflowItem(Workflow $workflow, $currentStep = null)
    {
        $result = new WorkflowItem();
        $result->setCurrentStep($currentStep);
        $result->setWorkflowName($workflow->getName());
        return $result;
    }

    protected function createWorkflowAttributesType(
        WorkflowRegistry $workflowRegistry = null,
        AttributeGuesser $attributeGuesser = null,
        DefaultValuesListener $defaultValuesListener = null,
        InitActionsListener $initActionListener = null,
        RequiredAttributesListener $requiredAttributesListener = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        if (!$workflowRegistry) {
            $workflowRegistry = $this->createWorkflowRegistryMock();
        }
        if (!$attributeGuesser) {
            $attributeGuesser = $this->createAttributeGuesserMock();
        }
        if (!$defaultValuesListener) {
            $defaultValuesListener = $this->createDefaultValuesListenerMock();
        }
        if (!$initActionListener) {
            $initActionListener = $this->createInitActionsListenerMock();
        }
        if (!$requiredAttributesListener) {
            $requiredAttributesListener = $this->createRequiredAttributesListenerMock();
        }
        if (!$dispatcher) {
            $dispatcher = $this->createDispatcherMock();
        }

        return new WorkflowAttributesType(
            $workflowRegistry,
            $attributeGuesser,
            $defaultValuesListener,
            $initActionListener,
            $requiredAttributesListener,
            new ContextAccessor(),
            $dispatcher
        );
    }

    protected function createFormRegistryMock()
    {
        return $this->getMockBuilder('Symfony\Component\Form\FormRegistry')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createWorkflowRegistryMock()
    {
        return $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getWorkflow'))
            ->getMock();
    }

    protected function createAttributeGuesserMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeGuesser')
            ->disableOriginalConstructor()
            ->setMethods(array('guessClassAttributeForm'))
            ->getMock();
    }

    protected function createDefaultValuesListenerMock()
    {
        return$this->getMockBuilder('Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener')
            ->disableOriginalConstructor()
            ->setMethods(array('initialize', 'setDefaultValues'))
            ->getMock();
    }

    protected function createInitActionsListenerMock()
    {
        return$this->getMockBuilder('Oro\Bundle\WorkflowBundle\Form\EventListener\InitActionsListener')
            ->disableOriginalConstructor()
            ->setMethods(array('initialize', 'executeInitAction'))
            ->getMock();
    }

    protected function createRequiredAttributesListenerMock()
    {
        return $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener')
            ->disableOriginalConstructor()
            ->setMethods(array('initialize', 'onPreSetData', 'onSubmit'))
            ->getMock();
    }

    protected function createDispatcherMock()
    {
        return $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
