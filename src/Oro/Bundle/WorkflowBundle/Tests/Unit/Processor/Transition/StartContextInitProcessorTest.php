<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\StartContextInitProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StartContextInitProcessorTest extends TestCase
{
    private Request&MockObject $request;
    private StartContextInitProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new StartContextInitProcessor();
        $this->request = $this->createMock(Request::class);
    }

    public function testSkipNotStartTransitionContext(): void
    {
        $context = new TransitionContext();
        $context->setIsStartTransition(false);
        $context->setRequest($this->request);

        $this->request->expects($this->never())
            ->method('get');

        $this->processor->process($context);

        $this->assertFalse($context->has(TransitionContext::INIT_DATA));
        $this->assertFalse($context->has(TransitionContext::ENTITY_ID));
    }

    public function testSetEntityIdAndInitDataArray(): void
    {
        $context = new TransitionContext();
        $context->setIsStartTransition(true);
        $context->setRequest($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('entityId')
            ->willReturn(42);

        $this->processor->process($context);

        $this->assertEquals(42, $context->get(TransitionContext::ENTITY_ID));
        $this->assertSame([], $context->get(TransitionContext::INIT_DATA));
    }

    /** Expected context behavior dependency check */
    public function testSetEntityIdAppliesEvenIfNoId(): void
    {
        $context = new TransitionContext();
        $context->setIsStartTransition(true);
        $context->setRequest($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('entityId')
            ->willReturn(null);

        $this->processor->process($context);

        $this->assertTrue($context->has(TransitionContext::ENTITY_ID));
        $this->assertEquals(null, $context->get(TransitionContext::ENTITY_ID));
        $this->assertSame([], $context->get(TransitionContext::INIT_DATA));
    }
}
