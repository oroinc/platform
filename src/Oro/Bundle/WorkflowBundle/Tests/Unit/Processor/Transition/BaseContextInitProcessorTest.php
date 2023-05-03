<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\BaseContextInitProcessor;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context\ResultTypeStub;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BaseContextInitProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var BaseContextInitProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->processor = new BaseContextInitProcessor($this->workflowManager);
    }

    public function testTransitionSpecified()
    {
        $this->expectException(\TypeError::class);
        if (PHP_VERSION_ID < 80000) {
            $messagePattern = 'Return value of %s::getTransitionName() must be of the type string, null returned';
        } else {
            $messagePattern = '%s::getTransitionName(): Return value must be of type %s, null returned';
        }
        $this->expectExceptionMessage(sprintf($messagePattern, TransitionContext::class));

        $this->processor->process(new TransitionContext());
    }

    public function testNoItemAndWorkflowSpecified()
    {
        $this->expectException(\TypeError::class);
        if (PHP_VERSION_ID < 80000) {
            $messagePattern = 'Return value of %s::getWorkflowName() must be of the type string, null returned';
        } else {
            $messagePattern = '%s::getWorkflowName(): Return value must be of type %s, null returned';
        }
        $this->expectExceptionMessage(sprintf($messagePattern, TransitionContext::class));

        $context = new TransitionContext();
        $context->setTransitionName('some_transition');

        $this->processor->process($context);
    }

    public function testWorkflowExceptionUnknownWorkflowCatch()
    {
        $context = new TransitionContext();
        $context->setTransitionName('transition');
        $context->setWorkflowName('unknown');

        $exception = new WorkflowNotFoundException('unknown');

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('unknown')
            ->willThrowException($exception);

        $this->processor->process($context);

        $this->assertEquals('normalize', $context->getFirstGroup());
        $this->assertSame($exception, $context->getError());
    }

    public function testWorkflowExceptionUnknownTransitionCatch()
    {
        $context = new TransitionContext();
        $context->setWorkflowName('known_workflow');
        $context->setTransitionName('unknown_transition');

        $exception = InvalidTransitionException::unknownTransition('unknown_transition');

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->willThrowException($exception);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('known_workflow')
            ->willReturn($workflow);

        $this->processor->process($context);

        $this->assertEquals('normalize', $context->getFirstGroup());
        $this->assertSame($exception, $context->getError());
    }

    public function testStartContextInit()
    {
        $request = $this->createMock(Request::class);

        $context = new TransitionContext();

        $context->setWorkflowName('the_workflow');
        $context->setTransitionName('the_transition');
        $context->setRequest($request);
        $context->setResultType(new ResultTypeStub('type', true));

        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        [$workflow, $transition] = $this->extractWorkflowAndTransition('the_workflow', 'the_transition');

        $transition->expects($this->once())
            ->method('isEmptyInitOptions')
            ->willReturn(false);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);

        $this->processor->process($context);

        $this->assertSame($workflow, $context->getWorkflow());
        $this->assertSame($transition, $context->getTransition());

        $this->assertFalse(
            $context->hasWorkflowItem(),
            'Start transition context should be initialized without WorkflowItem'
        );
        $this->assertTrue($context->get(TransitionContext::IS_START));
        $this->assertTrue($context->get(TransitionContext::HAS_INIT_OPTIONS));
        $this->assertTrue($context->get(TransitionContext::CUSTOM_FORM));
    }

    private function extractWorkflowAndTransition(string $workflowName, string $transitionName): array
    {
        $transition = $this->createMock(Transition::class);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('extractTransition')
            ->with($transitionName)
            ->willReturn($transition);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        return [$workflow, $transition];
    }

    public function testRegularContextInitAttributes()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getWorkflowName')
            ->willReturn('the_workflow');

        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        [$workflow, $transition] = $this->extractWorkflowAndTransition('the_workflow', 'the_transition');

        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);
        $transition->expects($this->never())
            ->method('isEmptyInitOptions');

        $context = new TransitionContext();

        $request = $this->createMock(Request::class);

        $context->setRequest($request);
        $context->setWorkflowItem($workflowItem);
        $context->setTransitionName('the_transition');
        $context->setResultType(new ResultTypeStub('type', false));

        $this->processor->process($context);

        $this->assertSame($workflow, $context->getWorkflow());
        $this->assertEquals('the_workflow', $context->getWorkflowName());
        $this->assertSame($workflowItem, $context->getWorkflowItem());
        $this->assertFalse($context->isCustomForm());
        $this->assertSame($transition, $context->getTransition());
    }
}
