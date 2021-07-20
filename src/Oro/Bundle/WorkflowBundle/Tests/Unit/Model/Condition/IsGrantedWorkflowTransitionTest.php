<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Condition\IsGrantedWorkflowTransition;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class IsGrantedWorkflowTransitionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|MockObject */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface|MockObject */
    protected $tokenAccessor;

    /** @var WorkflowManager|MockObject */
    protected $workflowManager;

    /** @var IsGrantedWorkflowTransition */
    protected $condition;

    /** @var AclGroupProviderInterface|MockObject */
    private $aclGroupProvider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->condition = new class(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->workflowManager,
            $this->aclGroupProvider
        ) extends IsGrantedWorkflowTransition {
            public function xgetTransitionName(): string
            {
                return $this->transitionName;
            }

            public function xgetTargetStepName(): string
            {
                return $this->targetStepName;
            }
        };
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertSame(IsGrantedWorkflowTransition::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider wrongOptionsDataProvider
     * @param array $options
     * @param string $exceptionMessage
     */
    public function testInitializeFail(array $options, $exceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->condition->initialize($options);
    }

    public function wrongOptionsDataProvider(): array
    {
        return [
            [[], 'Options must have 2 elements, but 0 given.'],
            [['test'], 'Options must have 2 elements, but 1 given.'],
            [['test', 'some', 'other'], 'Options must have 2 elements, but 3 given.'],
            [['', 'step'], 'Transition name must not be empty.'],
            [['transition', ''], 'Target step name must not be empty.'],
        ];
    }

    public function testInitialize()
    {
        $options = ['transition', 'step'];

        $this->condition->initialize($options);
        static::assertEquals('transition', $this->condition->xgetTransitionName());
        static::assertEquals('step', $this->condition->xgetTargetStepName());
    }

    public function testEvaluateNoUser()
    {
        $context = new WorkflowItem();
        $context->setEntity(new \stdClass());
        $context->setEntityClass(\stdClass::class);

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluatePerformTransitionsDenied()
    {
        $entity = new \stdClass();
        $context = new WorkflowItem();
        $context->setEntity($entity);
        $context->setEntityClass(\stdClass::class);
        $context->setWorkflowName('workflowName');
        $context->setEntityId(1);

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->aclGroupProvider->expects($this->once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $objectWrapper = new DomainObjectWrapper(
            $entity,
            new ObjectIdentity('workflow', 'workflowName')
        );

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('PERFORM_TRANSITIONS', $objectWrapper)
            ->willReturn(false);

        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluatePerformTransitionDenied()
    {
        $entity = new \stdClass();
        $context = new WorkflowItem();
        $context->setEntity($entity);
        $context->setEntityClass(\stdClass::class);
        $context->setWorkflowName('workflowName');
        $context->setEntityId(1);

        /** @var WorkflowStep|MockObject $step */
        $step = $this->createMock(WorkflowStep::class);
        $step->expects($this->any())
            ->method('getName')
            ->willReturn('currentStep');
        $context->setCurrentStep($step);

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->aclGroupProvider->expects($this->once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $objectWrapper = new DomainObjectWrapper(
            $entity,
            new ObjectIdentity('workflow', 'workflowName')
        );

        $fieldVote = new FieldVote($objectWrapper, 'transition|currentStep|step');

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['PERFORM_TRANSITIONS', $objectWrapper],
                ['PERFORM_TRANSITION', $fieldVote]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluatePerformTransitionsDeniedForStartTransition()
    {
        $entity = new \stdClass();
        $context = new WorkflowItem();
        $context->setEntity($entity);
        $context->setEntityClass(\stdClass::class);
        $context->setWorkflowName('workflowName');

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->aclGroupProvider->expects($this->once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $objectWrapper = new DomainObjectWrapper(
            'workflow:workflowName',
            new ObjectIdentity('workflow', 'workflowName')
        );

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->with('transition')
            ->willReturn($transition);
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($context)
            ->willReturn($workflow);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('PERFORM_TRANSITIONS', $objectWrapper)
            ->willReturn(false);

        $this->assertFalse($this->condition->evaluate($context));
    }

    public function testEvaluate()
    {
        $entity = new \stdClass();
        $context = new WorkflowItem();
        $context->setEntityClass(\stdClass::class);
        $context->setWorkflowName('workflowName');
        $context->setEntity($entity);
        $context->setEntityId(1);

        /** @var WorkflowStep|MockObject $step */
        $step = $this->createMock(WorkflowStep::class);
        $step->expects($this->any())
            ->method('getName')
            ->willReturn('currentStep');
        $context->setCurrentStep($step);

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->aclGroupProvider->expects($this->once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $objectWrapper = new DomainObjectWrapper(
            $entity,
            new ObjectIdentity('workflow', 'workflowName')
        );

        $fieldVote = new FieldVote($objectWrapper, 'transition|currentStep|step');

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['PERFORM_TRANSITIONS', $objectWrapper],
                ['PERFORM_TRANSITION', $fieldVote]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->assertTrue($this->condition->evaluate($context));
    }

    public function testEvaluateWithCustomGroup()
    {
        $entity = new \stdClass();
        $context = new WorkflowItem();
        $context->setEntityClass(\stdClass::class);
        $context->setWorkflowName('workflowName');
        $context->setEntity($entity);
        $context->setEntityId(1);

        $groupName = 'test_group';

        /** @var WorkflowStep|MockObject $step */
        $step = $this->createMock(WorkflowStep::class);
        $step->expects($this->any())
            ->method('getName')
            ->willReturn('currentStep');
        $context->setCurrentStep($step);

        $this->condition->initialize(['transition', 'step']);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->aclGroupProvider->expects($this->once())
            ->method('getGroup')
            ->willReturn($groupName);

        $objectWrapper = new DomainObjectWrapper(
            $entity,
            new ObjectIdentity('workflow', ObjectIdentityHelper::buildType('workflowName', $groupName))
        );

        $fieldVote = new FieldVote($objectWrapper, 'transition|currentStep|step');

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['PERFORM_TRANSITIONS', $objectWrapper],
                ['PERFORM_TRANSITION', $fieldVote]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->assertTrue($this->condition->evaluate($context));
    }
}
