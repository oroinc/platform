<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityWithWorkflow;

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
        $fireExceptions = true
    ) {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $entity = new EntityWithWorkflow();

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

        $workflow = $this->createWorkflow('test_workflow');
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
            ),
            'allowed_transition' => array(
                'expectedResult' => true,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
            ),
            'not_allowed_start_transition' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => false,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
            ),
            'allowed_start_transition' => array(
                'expectedResult' => true,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => true,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
            ),
            'unknown_transition_fire_exception' => array(
                'expectedException' => InvalidTransitionException::unknownTransition('test_transition'),
                'transitionExist' => false,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
            ),
            'unknown_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => false,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => true,
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
            ),
            'not_start_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => false,
                'stepAllowTransition' => true,
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
            ),
            'step_not_allow_transition_no_exception' => array(
                'expectedResult' => false,
                'transitionExist' => true,
                'transitionAllowed' => true,
                'isTransitionStart' => false,
                'hasCurrentStep' => true,
                'stepAllowTransition' => false,
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

        $entity = new EntityWithWorkflow();

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($workflowStepTwo->getName())
            ->will($this->returnValue($workflowStepTwo));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));
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

        $transition = $this->getTransitionMock('transition', false, $stepTwo);
        $transition->expects($this->once())
            ->method('transit')
            ->with($workflowItem);

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();
        $aclManager->expects($this->once())
            ->method('updateAclIdentities')
            ->with($workflowItem);

        $workflow = $this->createWorkflow(null, $doctrineHelper, $aclManager);
        $workflow->setDefinition($workflowDefinition);
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($stepOne, $stepTwo));
        $workflow->transit($workflowItem, 'transition');
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow "test" does not have step entity "stepTwo"
     */
    public function testTransitException()
    {
        $workflowStepOne = new WorkflowStep();
        $workflowStepOne->setName('stepOne');

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->will($this->returnValue($workflowStepOne));

        $stepOne = $this->getStepMock($workflowStepOne->getName());
        $stepOne->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->will($this->returnValue(true));

        $stepTwo = $this->getStepMock('stepTwo');

        $transition = $this->getTransitionMock('transition', false, $stepTwo);

        $workflow = $this->createWorkflow('test');
        $workflow->setDefinition($workflowDefinition);
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($stepOne));
        $workflow->transit($workflowItem, 'transition');
    }

    /**
     * @dataProvider startDataProvider
     * @param array $data
     * @param string $transitionName
     */
    public function testStart($data, $transitionName)
    {
        if (!$transitionName) {
            $expectedTransitionName = TransitionManager::DEFAULT_START_TRANSITION_NAME;
        } else {
            $expectedTransitionName = $transitionName;
        }

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('step_name');
        $step = $this->getStepMock($workflowStep->getName());

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

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($workflowStep->getName())
            ->will($this->returnValue($workflowStep));

        $entity = new EntityWithWorkflow();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $workflow = $this->createWorkflow();
        $workflow->setDefinition($workflowDefinition);
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

        $workflow = $this->createWorkflow(null, null, null, null, $transitionManager);

        $this->assertTrue($workflow->isTransitionAvailable($workflowItem, $transition, $errors));
    }

    public function testIsStartTransitionAvailable()
    {
        $data = array();
        $errors = new ArrayCollection();
        $transitionName = 'test_transition';

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

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
        $entity = new EntityWithWorkflow();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $workflow = $this->createWorkflow(null, null, null, null, $transitionManager);

        $workflow->setDefinition($workflowDefinition);
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
            array(
                array(
                    $this->getTransitionRecordMock('step1'),
                    $this->getTransitionRecordMock('step3'),
                    $this->getTransitionRecordMock('step2'),
                ),
                array('step1', 'step3', 'step2')
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
     * @param DoctrineHelper $doctrineHelper
     * @param AclManager $aclManager
     * @param AttributeManager $attributeManager
     * @param TransitionManager $transitionManager
     * @return Workflow
     */
    protected function createWorkflow(
        $workflowName = null,
        $doctrineHelper = null,
        $aclManager = null,
        $attributeManager = null,
        $transitionManager = null
    ) {
        if (!$doctrineHelper) {
            $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
                ->disableOriginalConstructor()
                ->getMock();
        }

        if (!$aclManager) {
            $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
                ->disableOriginalConstructor()
                ->getMock();
        }

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = new Workflow(
            $doctrineHelper,
            $aclManager,
            $restrictionManager,
            null,
            $attributeManager,
            $transitionManager
        );
        $workflow->setName($workflowName);

        return $workflow;
    }

    public function testGetAttributesMapping()
    {
        $attributeOne = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->getMock();
        $attributeOne->expects($this->once())
            ->method('getPropertyPath');
        $attributeOne->expects($this->never())
            ->method('getName');
        $attributeTwo = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->getMock();
        $attributeTwo->expects($this->atLeastOnce())
            ->method('getPropertyPath')
            ->will($this->returnValue('path'));
        $attributeTwo->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('name'));

        $attributes = array($attributeOne, $attributeTwo);
        $attributeManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeManager')
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
