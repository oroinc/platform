<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributeManager;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGettersAndSetters($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);
        $workflow = $this->createWorkflow();
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Workflow',
            call_user_func_array(array($workflow, $setter), array($value))
        );
        $this->assertEquals($value, call_user_func_array(array($workflow, $getter), array()));
    }

    public function propertiesDataProvider()
    {
        return array(
            'name' => array('name', 'test'),
            'label' => array('label', 'test'),
            'definition' => array('definition', $this->getMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'))
        );
    }

    public function testEnabled()
    {
        $workflow = $this->createWorkflow();
        $this->assertTrue($workflow->isEnabled());

        $workflow->setEnabled(false);
        $this->assertFalse($workflow->isEnabled());

        $workflow->setEnabled(true);
        $this->assertTrue($workflow->isEnabled());
    }

    public function testGetStepsEmpty()
    {
        $workflow = $this->createWorkflow();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $workflow->getStepManager()->getSteps());
    }

    public function testGetOrderedSteps()
    {
        $stepOne = new Step();
        $stepOne->setOrder(1);
        $stepTwo = new Step();
        $stepTwo->setOrder(2);
        $stepThree = new Step();
        $stepThree->setOrder(3);
        $steps = new ArrayCollection(array($stepTwo, $stepOne, $stepThree));

        $workflow = $this->createWorkflow();
        $workflow->getStepManager()->setSteps($steps);
        $ordered = $workflow->getStepManager()->getOrderedSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $ordered);
        $this->assertSame($stepOne, $ordered->get(0), 'Steps are not in correct order');
        $this->assertSame($stepTwo, $ordered->get(1), 'Steps are not in correct order');
        $this->assertSame($stepThree, $ordered->get(2), 'Steps are not in correct order');
    }

    public function testSetSteps()
    {
        $stepOne = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->getMock();
        $stepOne->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('step1'));

        $stepTwo = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->getMock();
        $stepTwo->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('step2'));

        $workflow = $this->createWorkflow();

        $workflow->getStepManager()->setSteps(array($stepOne, $stepTwo));
        $steps = $workflow->getStepManager()->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = array('step1' => $stepOne, 'step2' => $stepTwo);
        $this->assertEquals($expected, $steps->toArray());

        $stepsCollection = new ArrayCollection(array('step1' => $stepOne, 'step2' => $stepTwo));
        $workflow->getStepManager()->setSteps($stepsCollection);
        $steps = $workflow->getStepManager()->getSteps();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $steps);
        $expected = array('step1' => $stepOne, 'step2' => $stepTwo);
        $this->assertEquals($expected, $steps->toArray());
    }

    public function testGetTransitionsEmpty()
    {
        $workflow = $this->createWorkflow();
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $workflow->getTransitionManager()->getTransitions()
        );
    }

    public function testGetTransition()
    {
        $transition = $this->getTransitionMock('transition');

        $workflow = $this->createWorkflow();
        $workflow->getTransitionManager()->setTransitions(array($transition));

        $this->assertEquals($transition, $workflow->getTransitionManager()->getTransition('transition'));
    }

    public function testSetTransitions()
    {
        $transitionOne = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->getMock();
        $transitionOne->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('transition1'));

        $transitionTwo = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->getMock();
        $transitionTwo->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('transition2'));

        $workflow = $this->createWorkflow();

        $workflow->getTransitionManager()->setTransitions(array($transitionOne, $transitionTwo));
        $transitions = $workflow->getTransitionManager()->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = array('transition1' => $transitionOne, 'transition2' => $transitionTwo);
        $this->assertEquals($expected, $transitions->toArray());

        $transitionsCollection = new ArrayCollection(
            array('transition1' => $transitionOne, 'transition2' => $transitionTwo)
        );
        $workflow->getTransitionManager()->setTransitions($transitionsCollection);
        $transitions = $workflow->getTransitionManager()->getTransitions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $expected = array('transition1' => $transitionOne, 'transition2' => $transitionTwo);
        $this->assertEquals($expected, $transitions->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected transition argument type is string or Transition
     */
    public function testIsTransitionAllowedArgumentException()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->createWorkflow();
        $workflow->isTransitionAllowed($workflowItem, 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected transition argument type is string or Transition
     */
    public function testTransitAllowedArgumentException()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->createWorkflow();
        $workflow->transit($workflowItem, 1);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @expectedExceptionMessage Step "test_step" of workflow "test_workflow" doesn't have allowed transition "test_transition".
     */
    // @codingStandardsIgnoreEnd
    public function testIsTransitionAllowedStepHasNoAllowedTransitionException()
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->will($this->returnValue($workflowStep));
        $workflowItem->expects($this->once())
            ->method('getWorkflowName')
            ->will($this->returnValue('test_workflow'));

        $step = $this->getStepMock($workflowStep->getName());
        $step->expects($this->any())
            ->method('isAllowedTransition')
            ->with('test_transition')
            ->will($this->returnValue(false));

        $transition = $this->getTransitionMock('test_transition', false);

        $workflow = $this->createWorkflow('test_workflow');
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($step));

        $workflow->isTransitionAllowed($workflowItem, 'test_transition', null, true);
    }

    /**
     * @dataProvider isTransitionAllowedDataProvider
     */
    public function testIsTransitionAllowed(
        $expectedResult,
        $transitionExist,
        $transitionAllowed,
        $isTransitionStart,
        $hasCurrentStep,
        $stepAllowTransition,
        $isTransitionGranted,
        $fireExceptions = true
    ) {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $entity = new \DateTime();

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->will($this->returnValue('test_workflow'));
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->will($this->returnValue($hasCurrentStep ? $workflowStep : null));
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $step = $this->getStepMock('test_step');
        $step->expects($this->any())
            ->method('isAllowedTransition')
            ->with('test_transition')
            ->will($this->returnValue($stepAllowTransition));

        $errors = new ArrayCollection();

        $transition = $this->getTransitionMock('test_transition', $isTransitionStart);
        $transition->expects($this->any())
            ->method('isAllowed')
            ->with($workflowItem, $errors)
            ->will($this->returnValue($transitionAllowed));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->setMethods(array('isGranted'))
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->with('EDIT', $entity)
            ->will($this->returnValue($isTransitionGranted));

        $workflow = $this->createWorkflow('test_workflow', null, $securityFacade);
        if ($transitionExist) {
            $workflow->getTransitionManager()->setTransitions(array($transition));
        }
        $workflow->getStepManager()->setSteps(array($step));

        if ($expectedResult instanceof \Exception) {
            $this->setExpectedException(get_class($expectedResult), $expectedResult->getMessage());
        }

        $actualResult = $workflow->isTransitionAllowed($workflowItem, 'test_transition', $errors, $fireExceptions);

        if (is_bool($expectedResult)) {
            $this->assertEquals($actualResult, $expectedResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function isTransitionAllowedDataProvider()
    {
        return array(
            'not_allowed_transition' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => false,
                'isTransitionStart' => true,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'allowed_transition' => array(
                'expectedResult' => true,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'not_granted_transition' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => false,
            ),
            'not_allowed_start_transition' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => false,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'allowed_start_transition' => array(
                'expectedResult' => true,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => true,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'unknown_transition_fire_exception' => array(
                'expectedException' => InvalidTransitionException::unknownTransition('test_transition'),
                'transitionExist' => false,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'unknown_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => false,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
                'fireException' => false
            ),
            'not_start_transition_fire_exception' => array(
                'expectedException' => InvalidTransitionException::notStartTransition(
                    'test_workflow',
                    'test_transition'
                ),
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => false,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
            ),
            'not_start_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => false,
                'stepAllowTransition' => true,
                'isTransitionGranted' => true,
                'fireException' => false
            ),
            'step_not_allow_transition_fire_exception' => array(
                'expectedException' => InvalidTransitionException::stepHasNoAllowedTransition(
                    'test_workflow',
                    'test_step',
                    'test_transition'
                ),
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => false,
                'isTransitionGranted' => true,
            ),
            'step_not_allow_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => false,
                'isTransitionGranted' => true,
                'fireException' => false
            ),
        );
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @expectedTransitionMessage Step "stepOne" of workflow "test" doesn't have allowed transition "transition".
     */
    public function testTransitNotAllowedTransition()
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('stepOne');

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->will($this->returnValue($workflowStep));

        $step = $this->getStepMock($workflowStep->getName());
        $step->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->will($this->returnValue(false));

        $transition = $this->getTransitionMock('transition');

        $workflow = $this->createWorkflow('test');
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($step));
        $workflow->transit($workflowItem, 'transition');
    }

    public function testTransit()
    {
        $workflowStepOne = new WorkflowStep();
        $workflowStepOne->setName('stepOne');

        $workflowStepTwo = new WorkflowStep();
        $workflowStepTwo->setName('stepTwo');

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->will($this->returnValue($workflowStepOne));
        $workflowItem->expects($this->once())
            ->method('addTransitionRecord')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord'))
            ->will(
                $this->returnCallback(
                    function (WorkflowTransitionRecord $transitionRecord) {
                        self::assertEquals('transition', $transitionRecord->getTransitionName());
                        self::assertEquals('stepOne', $transitionRecord->getStepFrom()->getName());
                        self::assertEquals('stepTwo', $transitionRecord->getStepTo()->getName());
                    }
                )
            );

        $stepOne = $this->getStepMock($workflowStepOne->getName());
        $stepOne->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->will($this->returnValue(true));

        $stepTwo = $this->getStepMock('stepTwo');
        $stepTwo->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($workflowStepTwo));

        $transition = $this->getTransitionMock('transition', false, $stepTwo);
        $transition->expects($this->once())
            ->method('transit')
            ->with($workflowItem);
        $transition->expects($this->once())
            ->method('transit')
            ->with($workflowItem);

        $workflow = $this->createWorkflow();
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($stepOne, $stepTwo));
        $workflow->transit($workflowItem, 'transition');
    }

    public function testSetAttributes()
    {
        $attributeOne = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->getMock();
        $attributeOne->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('attr1'));

        $attributeTwo = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->getMock();
        $attributeTwo->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('attr2'));

        $workflow = $this->createWorkflow();

        $workflow->getAttributeManager()->setAttributes(array($attributeOne, $attributeTwo));
        $attributes = $workflow->getAttributeManager()->getAttributes();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $expected = array('attr1' => $attributeOne, 'attr2' => $attributeTwo);
        $this->assertEquals($expected, $attributes->toArray());

        $attributeCollection = new ArrayCollection(array('attr1' => $attributeOne, 'attr2' => $attributeTwo));
        $workflow->getAttributeManager()->setAttributes($attributeCollection);
        $attributes = $workflow->getAttributeManager()->getAttributes();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $expected = array('attr1' => $attributeOne, 'attr2' => $attributeTwo);
        $this->assertEquals($expected, $attributes->toArray());
    }

    public function testGetStepAttributes()
    {
        $attributes = new ArrayCollection();
        $workflow = $this->createWorkflow();
        $workflow->getAttributeManager()->setAttributes($attributes);
        $this->assertEquals($attributes, $workflow->getAttributeManager()->getAttributes());
    }

    public function testGetStep()
    {
        $step1 = $this->getStepMock('step1');
        $step2 = $this->getStepMock('step2');

        $workflow = $this->createWorkflow();
        $workflow->getStepManager()->setSteps(array($step1, $step2));

        $this->assertEquals($step1, $workflow->getStepManager()->getStep('step1'));
        $this->assertEquals($step2, $workflow->getStepManager()->getStep('step2'));
    }

    /**
     * @dataProvider startDataProvider
     * @param array $data
     * @param string $transitionName
     */
    public function testStart($data, $transitionName)
    {
        if (!$transitionName) {
            $expectedTransitionName = Workflow::DEFAULT_START_TRANSITION_NAME;
        } else {
            $expectedTransitionName = $transitionName;
        }

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('step_name');
        $step = $this->getStepMock($workflowStep->getName());
        $step->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($workflowStep));

        $transition = $this->getTransitionMock($expectedTransitionName, true, $step);
        $transition->expects($this->once())
            ->method('transit')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem'))
            ->will(
                $this->returnCallback(
                    function (WorkflowItem $workflowItem) use ($workflowStep) {
                        $workflowItem->setCurrentStep($workflowStep);
                    }
                )
            );

        $entity = new \DateTime();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $workflow = $this->createWorkflow();
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getAttributeManager()->setAttributes(array($entityAttribute));
        $workflow->getAttributeManager()->setEntityAttributeName($entityAttribute->getName());
        $item = $workflow->start($entity, $data, $transitionName);
        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem', $item);
        $this->assertEquals($entity, $item->getEntity());
        $this->assertEquals(array_merge($data, array('entity' => $entity)), $item->getData()->getValues());
    }

    public function startDataProvider()
    {
        return array(
            array(array(), null),
            array(array('test' => 'test'), 'test')
        );
    }

    public function testGetStartTransitions()
    {
        $allowedStartTransition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $allowedStartTransition->expects($this->once())
            ->method('isStart')
            ->will($this->returnValue(true));

        $allowedTransition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $allowedTransition->expects($this->once())
            ->method('isStart')
            ->will($this->returnValue(false));

        $transitions = new ArrayCollection(
            array(
                $allowedStartTransition,
                $allowedTransition
            )
        );
        $expected = new ArrayCollection(array($allowedStartTransition));

        $workflow = $this->createWorkflow();
        $workflow->getTransitionManager()->setTransitions($transitions);
        $this->assertEquals($expected, $workflow->getTransitionManager()->getStartTransitions());
    }

    public function testGetAttribute()
    {
        $attribute = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $attributes = new ArrayCollection(array('test' => $attribute));

        $workflow = $this->createWorkflow();
        $workflow->getAttributeManager()->setAttributes($attributes);
        $this->assertSame($attribute, $workflow->getAttributeManager()->getAttribute('test'));
    }

    protected function getStepMock($name)
    {
        $step = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->disableOriginalConstructor()
            ->getMock();
        $step->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $step;
    }

    protected function getTransitionMock($name, $isStart = false, $step = null)
    {
        $transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $transition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        if ($isStart) {
            $transition->expects($this->any())
                ->method('isStart')
                ->will($this->returnValue($isStart));
        }
        if ($step) {
            $transition->expects($this->any())
                ->method('getStepTo')
                ->will($this->returnValue($step));
        }

        return $transition;
    }

    public function testGetAllowedTransitions()
    {
        $firstTransition = new Transition();
        $firstTransition->setName('first_transition');

        $secondTransition = new Transition();
        $secondTransition->setName('second_transition');

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $step = new Step();
        $step->setName($workflowStep->getName());
        $step->setAllowedTransitions(array($secondTransition->getName()));
        $step->setEntity($workflowStep);

        $workflow = $this->createWorkflow();
        $workflow->getStepManager()->setSteps(array($step));
        $workflow->getTransitionManager()->setTransitions(array($firstTransition, $secondTransition));

        $workflowItem = new WorkflowItem();
        $workflowItem->setCurrentStep($workflowStep);

        $actualTransitions = $workflow->getTransitionsByWorkflowItem($workflowItem);
        $this->assertEquals(array($secondTransition), $actualTransitions->getValues());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\UnknownStepException
     * @expectedExceptionMessage Step "unknown_step" not found
     */
    public function testGetAllowedTransitionsUnknownStepException()
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('unknown_step');

        $workflowItem = new WorkflowItem();
        $workflowItem->setCurrentStep($workflowStep);

        $workflow = $this->createWorkflow();
        $workflow->getTransitionsByWorkflowItem($workflowItem);
    }

    public function testIsTransitionAvailable()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $errors = new ArrayCollection();
        $transitionName = 'test_transition';
        $transition = $this->getTransitionMock($transitionName);
        $transition->expects($this->once())
            ->method('isAvailable')
            ->with($workflowItem, $errors)
            ->will($this->returnValue(true));
        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->with($transition)
            ->will($this->returnValue($transition));
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->setMethods(array('isGranted'))
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $workflow = $this->createWorkflow(null, null, $securityFacade, null, $transitionManager);

        $this->assertTrue($workflow->isTransitionAvailable($workflowItem, $transition, $errors));
    }

    public function testIsStartTransitionAvailable()
    {
        $data = array();
        $errors = new ArrayCollection();
        $transitionName = 'test_transition';
        $transition = $this->getTransitionMock($transitionName);
        $transition->expects($this->once())
            ->method('isAvailable')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem'), $errors)
            ->will($this->returnValue(true));
        $transitionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->with($transition)
            ->will($this->returnValue($transition));
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->setMethods(array('isGranted'))
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $entity = new \DateTime();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $workflow = $this->createWorkflow(null, null, $securityFacade, null, $transitionManager);
        $workflow->getAttributeManager()->setAttributes(array($entityAttribute));
        $workflow->getAttributeManager()->setEntityAttributeName($entityAttribute->getName());

        $this->assertTrue($workflow->isStartTransitionAvailable($transition, $entity, $data, $errors));
    }

    /**
     * @dataProvider passedStepsDataProvider
     * @param array $records
     * @param array $expected
     */
    public function testGetPassedStepsByWorkflowItem($records, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getTransitionRecords')
            ->will($this->returnValue($records));

        $stepsOne = $this->getStepMock('step1');
        $stepsOne->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(1));
        $stepsTwo = $this->getStepMock('step2');
        $stepsTwo->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(2));
        $stepsThree = $this->getStepMock('step3');
        $stepsThree->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(2));

        $workflow = $this->createWorkflow();
        $workflow->getStepManager()->setSteps(array($stepsOne, $stepsTwo, $stepsThree));

        $passedSteps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $passedSteps);
        $actual = array();
        /** @var Step $step */
        foreach ($passedSteps as $step) {
            $actual[] = $step->getName();
        }
        $this->assertEquals($expected, $actual);
    }

    public function passedStepsDataProvider()
    {
        return array(
            array(
                array(
                    $this->getTransitionRecordMock('step1')
                ),
                array('step1')
            ),
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                ),
                array('step1', 'step2')
            ),
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                ),
                array('step1', 'step2')
            ),
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step3'),
                ),
                array('step1', 'step3')
            ),
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step3'),
                ),
                array('step1', 'step3')
            ),
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step2'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step3'),
                ),
                array('step1', 'step3')
            ),
        );
    }

    protected function getTransitionRecordMock($stepToName)
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName($stepToName);

        $record = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord')
            ->disableOriginalConstructor()
            ->getMock();
        $record->expects($this->any())
            ->method('getStepTo')
            ->will($this->returnValue($workflowStep));
        return $record;
    }

    /**
     * @param null|string $workflowName
     * @return Workflow
     */

    /**
     * @param string $workflowName
     * @param EntityConnector $entityConnector
     * @param SecurityFacade $securityFacade
     * @param AttributeManager $attributeManager
     * @param TransitionManager $transitionManager
     * @return Workflow
     */
    protected function createWorkflow(
        $workflowName = null,
        $entityConnector = null,
        $securityFacade = null,
        $attributeManager = null,
        $transitionManager = null
    ) {
        if (!$entityConnector) {
            $entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
                ->disableOriginalConstructor()
                ->getMock();
        }

        if (!$securityFacade) {
            $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
                ->disableOriginalConstructor()
                ->getMock();
        }
        $workflow = new Workflow($entityConnector, $securityFacade, null, $attributeManager, $transitionManager);
        $workflow->setName($workflowName);
        return $workflow;
    }

    public function testGetAttributesMapping()
    {
        $attributeOne = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->getMock();
        $attributeOne->expects($this->once())
            ->method('getPropertyPath');
        $attributeOne->expects($this->never())
            ->method('getName');
        $attributeTwo = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Attribute')
            ->getMock();
        $attributeTwo->expects($this->atLeastOnce())
            ->method('getPropertyPath')
            ->will($this->returnValue('path'));
        $attributeTwo->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('name'));

        $attributes = array($attributeOne, $attributeTwo);
        $attributeManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\AttributeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $attributeManager->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($attributes));
        $workflow = $this->createWorkflow(null, null, null, $attributeManager);
        $expected = array('name' => 'path');
        $this->assertEquals($expected, $workflow->getAttributesMapping());
    }
}
