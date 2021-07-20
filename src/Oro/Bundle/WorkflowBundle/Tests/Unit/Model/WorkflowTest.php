<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableAssembler;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityWithWorkflow;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class WorkflowTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGettersAndSetters(string $property, $value): void
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

    public function propertiesDataProvider(): array
    {
        return array(
            'definition' => array(
                'definition',
                $this->createMock(WorkflowDefinition::class)
            )
        );
    }

    public function testIsTransitionAllowedArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected transition argument type is string or Transition');

        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflow = $this->createWorkflow();
        $workflow->isTransitionAllowed($workflowItem, 1);
    }

    public function testTransitAllowedArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected transition argument type is string or Transition');

        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflow = $this->createWorkflow();
        $workflow->transit($workflowItem, 1);
    }

    public function testIsTransitionAllowedStepHasNoAllowedTransitionException(): void
    {
        $this->expectException(\Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException::class);
        $this->expectExceptionMessage(
            'Step "test_step" of workflow "test_workflow" doesn\'t have allowed transition "test_transition".'
        );

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);
        $workflowItem->expects($this->once())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $step = $this->getStepMock($workflowStep->getName());
        $step->expects($this->any())
            ->method('isAllowedTransition')
            ->with('test_transition')
            ->willReturn(false);

        $transition = $this->getTransitionMock('test_transition', false);

        $workflow = $this->createWorkflow('test_workflow');
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($step));

        $workflow->isTransitionAllowed($workflowItem, 'test_transition', null, true);
    }

    /**
     * @dataProvider isTransitionAllowedDataProvider
     * @param mixed $expectedResult
     * @param bool $transitionExist
     * @param bool $transitionAllowed
     * @param bool $isTransitionStart
     * @param bool $hasCurrentStep
     * @param bool $stepAllowTransition
     * @param bool $fireExceptions
     */
    public function testIsTransitionAllowed(
        $expectedResult,
        bool $transitionExist,
        bool $transitionAllowed,
        bool $isTransitionStart,
        bool $hasCurrentStep,
        bool $stepAllowTransition,
        bool $fireExceptions = true
    ): void {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('test_step');

        $entity = new EntityWithWorkflow();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($hasCurrentStep ? $workflowStep : null);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $step = $this->getStepMock('test_step');
        $step->expects($this->any())
            ->method('isAllowedTransition')
            ->with('test_transition')
            ->willReturn($stepAllowTransition);

        $errors = new ArrayCollection();

        $transition = $this->getTransitionMock('test_transition', $isTransitionStart);
        $transition->expects($this->any())
            ->method('isAllowed')
            ->with($workflowItem, $errors)
            ->willReturn($transitionAllowed);

        $workflow = $this->createWorkflow('test_workflow');
        if ($transitionExist) {
            $workflow->getTransitionManager()->setTransitions(array($transition));
        }
        $workflow->getStepManager()->setSteps(array($step));

        if ($expectedResult instanceof \Exception) {
            $this->expectException(get_class($expectedResult));
            $this->expectExceptionMessage($expectedResult->getMessage());
        }

        $actualResult = $workflow->isTransitionAllowed($workflowItem, 'test_transition', $errors, $fireExceptions);

        if (is_bool($expectedResult)) {
            $this->assertEquals($actualResult, $expectedResult);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isTransitionAllowedDataProvider(): array
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

    public function testTransitNotAllowedTransition(): void
    {
        $workflowName = 'testWorkflow';
        $stepName = 'stepOne';

        $this->expectException(\Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException::class);
        $this->expectExceptionMessage(\sprintf(
            'Step "%s" of workflow "%s" doesn\'t have allowed transition "transition".',
            $stepName,
            $workflowName
        ));

        $workflowStep = new WorkflowStep();
        $workflowStep->setName($stepName);

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->onlyMethods(['getCurrentStep'])
            ->getMock();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);
        $workflowItem->setWorkflowName($workflowName);

        $step = $this->getStepMock($workflowStep->getName());
        $step->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->willReturn(false);

        $transition = $this->getTransitionMock('transition');

        $workflow = $this->createWorkflow($workflowName);
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($step));
        $workflow->transit($workflowItem, 'transition');
    }

    public function testTransit(): void
    {
        $workflowStepOne = new WorkflowStep();
        $workflowStepOne->setName('stepOne');

        $workflowStepTwo = new WorkflowStep();
        $workflowStepTwo->setName('stepTwo');

        $entity = new EntityWithWorkflow();

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($workflowStepTwo->getName())
            ->willReturn($workflowStepTwo);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($workflowStepOne);
        $workflowItem->expects($this->once())
            ->method('addTransitionRecord')
            ->with($this->isInstanceOf(WorkflowTransitionRecord::class))
            ->willReturnCallback(
                static function (WorkflowTransitionRecord $transitionRecord) {
                    self::assertEquals('transition', $transitionRecord->getTransitionName());
                    self::assertEquals('stepOne', $transitionRecord->getStepFrom()->getName());
                    self::assertEquals('stepTwo', $transitionRecord->getStepTo()->getName());
                }
            );

        $stepOne = $this->getStepMock($workflowStepOne->getName());
        $stepOne->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->willReturn(true);

        $stepTwo = $this->getStepMock('stepTwo');

        $errors = new ArrayCollection();

        $transition = $this->getTransitionMock('transition', false, $stepTwo);
        $transition->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $errors);

        $aclManager = $this->createMock(AclManager::class);
        $aclManager->expects($this->once())
            ->method('updateAclIdentities')
            ->with($workflowItem);

        $workflow = $this->createWorkflow(null, $aclManager);
        $workflow->setDefinition($workflowDefinition);
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($stepOne, $stepTwo));
        $workflow->transit($workflowItem, 'transition', $errors);
    }

    public function testTransitException(): void
    {
        $this->expectException(\Oro\Bundle\WorkflowBundle\Exception\WorkflowException::class);
        $this->expectExceptionMessage('Workflow "test" does not have step entity "stepTwo"');

        $workflowStepOne = new WorkflowStep();
        $workflowStepOne->setName('stepOne');

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($workflowStepOne);

        $stepOne = $this->getStepMock($workflowStepOne->getName());
        $stepOne->expects($this->once())
            ->method('isAllowedTransition')
            ->with('transition')
            ->willReturn(true);

        $stepTwo = $this->getStepMock('stepTwo');

        $transition = $this->getTransitionMock('transition', false, $stepTwo);

        $workflow = $this->createWorkflow();
        $workflow->setDefinition($workflowDefinition);
        $workflowDefinition->expects($this->once())->method('getName')->willReturn('test');
        $workflow->getTransitionManager()->setTransitions(array($transition));
        $workflow->getStepManager()->setSteps(array($stepOne));
        $workflow->transit($workflowItem, 'transition');
    }

    /**
     * @dataProvider startDataProvider
     */
    public function testStart(array $data, ?string $transitionName): void
    {
        if (!$transitionName) {
            $expectedTransitionName = TransitionManager::DEFAULT_START_TRANSITION_NAME;
        } else {
            $expectedTransitionName = $transitionName;
        }

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('step_name');

        $errors = new ArrayCollection();

        $transition = $this->assertTransitionCalled($workflowStep, $expectedTransitionName, $errors);

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($workflowStep->getName())
            ->willReturn($workflowStep);
        $workflowDefinition->expects($this->any())->method('getName')->willReturn('test_wf_name');

        $entity = new EntityWithWorkflow();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $this->assertDoctrineHelperCalled($entity, 'test_wf_name');

        $workflow = $this->createWorkflow();
        $workflow->setDefinition($workflowDefinition);
        $workflow->getTransitionManager()->setTransitions([$transition]);
        $workflow->getAttributeManager()->setAttributes([$entityAttribute]);
        $workflow->getAttributeManager()->setEntityAttributeName($entityAttribute->getName());

        $item = $workflow->start($entity, $data, $transitionName, $errors);

        $this->assertInstanceOf(WorkflowItem::class, $item);
        $this->assertEquals($entity, $item->getEntity());
        $this->assertEquals(array_merge($data, ['entity' => $entity]), $item->getData()->getValues());
    }

    public function startDataProvider(): array
    {
        return [
            [[], null],
            [['test' => 'test'], 'test']
        ];
    }

    public function testStartWithNotRelatedEntity(): void
    {
        $entityClass = 'stdClass';
        $entityId = 42;
        $entityAttributeName = 'test_entity';

        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')->willReturn($repository);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback('Doctrine\Common\Util\ClassUtils::getClass');
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                static function ($actual) use ($entityClass, $entityId) {
                    return $actual instanceof $entityClass ? $entityId : null;
                }
            );

        $workflowStep = (new WorkflowStep())->setName('step_name');
        $transition = $this->assertTransitionCalled($workflowStep, TransitionManager::DEFAULT_START_TRANSITION_NAME);

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($workflowStep->getName())
            ->willReturn($workflowStep);
        $workflowDefinition->expects($this->once())->method('getEntityAttributeName')->willReturn($entityAttributeName);
        $workflowDefinition->expects($this->any())->method('getName')->willReturn('test_wf_name');

        $entityAttribute = (new Attribute())->setName('entity');

        $workflow = $this->createWorkflow();
        $workflow->setDefinition($workflowDefinition);
        $workflow->getTransitionManager()->setTransitions([$transition]);
        $workflow->getAttributeManager()->setAttributes([$entityAttribute]);
        $workflow->getAttributeManager()->setEntityAttributeName($entityAttribute->getName());

        $item = $workflow->start(new EntityWithWorkflow(), [$entityAttributeName => new $entityClass]);

        $this->assertInstanceOf(WorkflowItem::class, $item);
        $this->assertEquals(new \stdClass(), $item->getEntity());
        $this->assertEquals(
            ['entity' => new EntityWithWorkflow(), $entityAttributeName => new $entityClass],
            $item->getData()->getValues()
        );
    }

    public function testGetVariables(): void
    {
        $variables = new ArrayCollection([$this->createMock(Variable::class)]);

        /** @var VariableAssembler|\PHPUnit\Framework\MockObject\MockObject $variableAssembler */
        $variableAssembler = $this->createMock(VariableAssembler::class);
        $variableAssembler->expects($this->any())
            ->method('assemble')
            ->willReturn($variables);

        /** @var VariableManager|\PHPUnit\Framework\MockObject\MockObject $variableManager */
        $variableManager = $this->createMock(VariableManager::class);
        $variableManager->expects($this->any())
            ->method('getVariableAssembler')
            ->willReturn($variableAssembler);

        $workflow = $this->createWorkflow(null, null, null, null, $variableManager);

        $this->assertEquals($variables, $workflow->getVariables());
    }

    public function testGetCachedVariables(): void
    {
        $variables = new ArrayCollection([$this->createMock(Variable::class)]);

        /** @var VariableAssembler|\PHPUnit\Framework\MockObject\MockObject $variableAssembler */
        $variableAssembler = $this->createMock(VariableAssembler::class);
        $variableAssembler->expects($this->once())
            ->method('assemble')
            ->willReturn($variables);

        /** @var VariableManager|\PHPUnit\Framework\MockObject\MockObject $variableManager */
        $variableManager = $this->createMock(VariableManager::class);
        $variableManager->expects($this->any())
            ->method('getVariableAssembler')
            ->willReturn($variableAssembler);

        $workflow = $this->createWorkflow(null, null, null, null, $variableManager);

        //assemble method was called once, method twice, validates cache
        $workflow->getVariables();
        $this->assertEquals($variables, $workflow->getVariables());
    }

    /**
     * @param WorkflowStep $step
     * @param string $expectedTransitionName
     * @param Collection|null $errors
     *
     * @return Transition|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function assertTransitionCalled(
        WorkflowStep $step,
        string $expectedTransitionName,
        ?Collection $errors = null
    ): Transition {
        $transition = $this->getTransitionMock($expectedTransitionName, true, $this->getStepMock($step->getName()));
        $transition->expects($this->once())
            ->method('transit')
            ->with($this->isInstanceOf(WorkflowItem::class), $errors)
            ->willReturnCallback(
                static function (WorkflowItem $workflowItem) use ($step) {
                    $workflowItem->setCurrentStep($step);
                }
            );

        return $transition;
    }

    /**
     * @param string $name
     * @return Step|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStepMock(string $name): Step
    {
        $step = $this->createMock(Step::class);
        $step->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $step;
    }

    /**
     * @param string $name
     * @param bool $isStart
     * @param Step|null $step
     * @return Transition|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTransitionMock(string $name, bool $isStart = false, ?Step $step = null): Transition
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        if ($isStart) {
            $transition->expects($this->any())
                ->method('isStart')
                ->willReturn($isStart);
        }
        if ($step) {
            $transition->expects($this->any())
                ->method('getStepTo')
                ->willReturn($step);
        }

        return $transition;
    }

    public function testGetAllowedTransitions(): void
    {
        $firstTransition = $this->getTransitionMock('first_transition');
        $secondTransition = $this->getTransitionMock('second_transition');

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

    public function testGetAllowedTransitionsUnknownStepException(): void
    {
        $this->expectException(\Oro\Bundle\WorkflowBundle\Exception\UnknownStepException::class);
        $this->expectExceptionMessage('Step "unknown_step" not found');

        $workflowStep = new WorkflowStep();
        $workflowStep->setName('unknown_step');

        $workflowItem = new WorkflowItem();
        $workflowItem->setCurrentStep($workflowStep);

        $workflow = $this->createWorkflow();
        $workflow->getTransitionsByWorkflowItem($workflowItem);
    }

    public function testIsTransitionAvailable(): void
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();
        $transitionName = 'test_transition';
        $transition = $this->getTransitionMock($transitionName);
        $transition->expects($this->once())
            ->method('isAvailable')
            ->with($workflowItem, $errors)
            ->willReturn(true);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->with($transition)
            ->willReturn($transition);

        $workflow = $this->createWorkflow(null, null, null, $transitionManager);

        $this->assertTrue($workflow->isTransitionAvailable($workflowItem, $transition, $errors));
    }

    public function testIsStartTransitionAvailable(): void
    {
        $data = array();
        $errors = new ArrayCollection();
        $transitionName = 'test_transition';

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);

        $transition = $this->getTransitionMock($transitionName);
        $transition->expects($this->once())
            ->method('isAvailable')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem'), $errors)
            ->willReturn(true);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->with($transition)
            ->willReturn($transition);
        $entity = new EntityWithWorkflow();
        $entityAttribute = new Attribute();
        $entityAttribute->setName('entity');

        $this->assertDoctrineHelperCalled($entity, null);

        $workflow = $this->createWorkflow(null, null, null, $transitionManager);

        $workflow->setDefinition($workflowDefinition);
        $workflow->getAttributeManager()->setAttributes(array($entityAttribute));
        $workflow->getAttributeManager()->setEntityAttributeName($entityAttribute->getName());

        $this->assertTrue($workflow->isStartTransitionAvailable($transition, $entity, $data, $errors));
    }

    /**
     * @dataProvider passedStepsDataProvider
     */
    public function testGetPassedStepsByWorkflowItem(array $records, array $expected): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getTransitionRecords')
            ->willReturn($records);

        $stepsOne = $this->getStepMock('step1');
        $stepsOne->expects($this->any())
            ->method('getOrder')
            ->willReturn(1);
        $stepsTwo = $this->getStepMock('step2');
        $stepsTwo->expects($this->any())
            ->method('getOrder')
            ->willReturn(2);
        $stepsThree = $this->getStepMock('step3');
        $stepsThree->expects($this->any())
            ->method('getOrder')
            ->willReturn(2);

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

    public function passedStepsDataProvider(): array
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

    /**
     * @param string $stepToName
     * @return WorkflowTransitionRecord|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTransitionRecordMock($stepToName): WorkflowTransitionRecord
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName($stepToName);

        $record = $this->createMock(WorkflowTransitionRecord::class);
        $record->expects($this->any())
            ->method('getStepTo')
            ->willReturn($workflowStep);

        return $record;
    }

    /**
     * @param string $workflowName
     * @param AclManager $aclManager
     * @param AttributeManager $attributeManager
     * @param TransitionManager $transitionManager
     * @param VariableManager $variableManager
     * @return Workflow
     */
    protected function createWorkflow(
        string $workflowName = null,
        AclManager $aclManager = null,
        AttributeManager $attributeManager = null,
        TransitionManager $transitionManager = null,
        VariableManager $variableManager = null
    ): Workflow {
        if (!$aclManager) {
            $aclManager = $this->createMock(AclManager::class);
        }

        /** @var RestrictionManager|\PHPUnit\Framework\MockObject\MockObject $restrictionManager */
        $restrictionManager = $this->createMock(RestrictionManager::class);

        if (!$variableManager) {
            /** @var VariableAssembler|\PHPUnit\Framework\MockObject\MockObject $variableAssembler */
            $variableAssembler = $this->createMock(VariableAssembler::class);
            $variableAssembler->expects($this->any())
                ->method('assemble')
                ->willReturn(new ArrayCollection());

            /** @var VariableManager|\PHPUnit\Framework\MockObject\MockObject $variableManager */
            $variableManager = $this->createMock(VariableManager::class);
            $variableManager->expects($this->any())
                ->method('getVariableAssembler')
                ->willReturn($variableAssembler);
        }

        $workflow = new Workflow(
            $this->doctrineHelper,
            $aclManager,
            $restrictionManager,
            null,
            $attributeManager,
            $transitionManager,
            $variableManager
        );

        $definition = new WorkflowDefinition();
        $definition->setName($workflowName);
        $workflow->setDefinition($definition);

        return $workflow;
    }

    public function testGetAttributesMapping(): void
    {
        $attributeOne = $this->createMock(Attribute::class);
        $attributeOne->expects($this->once())
            ->method('getPropertyPath');
        $attributeOne->expects($this->never())
            ->method('getName');
        $attributeTwo = $this->createMock(Attribute::class);
        $attributeTwo->expects($this->atLeastOnce())
            ->method('getPropertyPath')
            ->willReturn('path');
        $attributeTwo->expects($this->once())
            ->method('getName')
            ->willReturn('name');

        $attributes = array($attributeOne, $attributeTwo);
        $attributeManager = $this->createMock(AttributeManager::class);
        $attributeManager->expects($this->once())
            ->method('getAttributes')
            ->willReturn($attributes);

        $workflow = $this->createWorkflow(null, null, $attributeManager);
        $expected = array('name' => 'path');
        $this->assertEquals($expected, $workflow->getAttributesMapping());
    }

    /**
     * @dataProvider configurationOptionProvider
     */
    public function testGetConfigurationOption(array $data, string $property, string $node): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $workflow = $this->createWorkflow();
        $workflow->getDefinition()->setConfiguration([
            $node => $data,
        ]);
        $this->assertEquals($data, $accessor->getValue($workflow, $property));
    }

    public function configurationOptionProvider(): \Generator
    {
        yield [
            'data' => ['route1' => ['trans1']],
            'property' => 'initRoutes',
            'node' => WorkflowConfiguration::NODE_INIT_ROUTES
        ];

        yield [
            'data' => ['entity1' => ['trans1']],
            'property' => 'initEntities',
            'node' => WorkflowConfiguration::NODE_INIT_ENTITIES
        ];
    }

    public function testGetInitDatagrids(): void
    {
        $workflow = $this->createWorkflow();
        $data = ['datagrid1' => ['trans1']];
        $workflow->getDefinition()->setConfiguration([
            WorkflowConfiguration::NODE_INIT_DATAGRIDS => $data,
        ]);
        $this->assertEquals($data, $workflow->getInitDatagrids());
    }

    public function testGetWorkflowItemByEntityId(): void
    {
        $workflow = $this->createWorkflow('test_workflow');
        $definition = $workflow->getDefinition();
        $definition->setRelatedEntity('entity');
        $entity = new \stdClass();

        $repository = $this->createMock(WorkflowItemRepository::class);
        $repository->expects($this->once())
            ->method('findOneByEntityMetadata')
            ->with('entity', 10, 'test_workflow')
            ->willReturn($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->assertSame($entity, $workflow->getWorkflowItemByEntityId(10));
    }

    protected function assertDoctrineHelperCalled(object $entity, ?string $workflowName): void
    {
        $entityClass = 'stdClass';
        $entityId = 42;

        $repository = $this->createMock(WorkflowItemRepository::class);
        $repository->expects($this->once())
            ->method('findOneByEntityMetadata')
            ->with($entityClass, $entityId, $workflowName)
            ->willReturn(null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);
    }
}
