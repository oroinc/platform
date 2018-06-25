<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\FormSubmitLayoutAjaxResponseProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FormSubmitLayoutAjaxResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormSubmitLayoutAjaxResponseProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new FormSubmitLayoutAjaxResponseProcessor();
    }

    public function testCreateJsonResponseResult()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getResult')->willReturn(new WorkflowResult(['result data']));

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('isXmlHttpRequest')->willReturn(true);

        $context = new TransitionContext();
        $context->setRequest($request);
        $context->setSaved(true);
        $context->setWorkflowItem($workflowItem);
        $context->setResultType($this->createMock(LayoutDialogResultType::class));

        $this->processor->process($context);

        /** @var JsonResponse $result */
        $result = $context->getResult();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('{"workflowItem":{"result":["result data"]}}', $result->getContent());
        $this->assertTrue($context->isProcessed());
    }

    public function testShouldSkipNotSaved()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(false);

        $context->expects($this->never())->method('setResult');

        $this->processor->process($context);
    }

    public function testShouldSkipUnsupportedResultType()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(true);
        $context->expects($this->once())
            ->method('getResultType')->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('setResult');

        $this->processor->process($context);
    }

    public function testShouldSkipNotXhr()
    {
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('isXmlHttpRequest')->willReturn(false);

        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(true);
        $context->expects($this->once())
            ->method('getResultType')->willReturn($this->createMock(LayoutDialogResultType::class));
        $context->expects($this->once())->method('getRequest')->willReturn($request);
        $context->expects($this->never())->method('setResult');

        $this->processor->process($context);
    }
}
