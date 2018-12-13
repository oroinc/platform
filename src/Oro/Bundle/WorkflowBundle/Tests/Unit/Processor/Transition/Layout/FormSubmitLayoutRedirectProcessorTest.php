<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\FormSubmitLayoutRedirectProcessor;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FormSubmitLayoutRedirectProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormSubmitLayoutRedirectProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new FormSubmitLayoutRedirectProcessor();
    }

    public function testRedirectFromWorkflowItemResult()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn(new WorkflowData(['redirectUrl' => '///workflow result url']));

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        $this->assertEquals('///workflow result url', $context->getResult()->getTargetUrl());
        $this->assertTrue($context->isProcessed());
    }

    public function testRedirectFromRequestHeadersReferer()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->any())->method('getResult')->willReturn(new WorkflowData([]));

        $request = new Request();
        $request->headers = new HeaderBag(['referer' => '///referer url']);

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setRequest($request);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        $this->assertEquals('///referer url', $context->getResult()->getTargetUrl());
        $this->assertTrue($context->isProcessed());
    }

    public function testRedirectFromRequestOriginalUrl()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->any())->method('getResult')->willReturn(new WorkflowData([]));

        $request = new Request();
        $request->query = new ParameterBag(['originalUrl' => '///original url']);

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setRequest($request);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        $this->assertEquals('///original url', $context->getResult()->getTargetUrl());
        $this->assertTrue($context->isProcessed());
    }

    public function testRedirectToRootAsDefaultIfAnythingDefined()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->any())->method('getResult')->willReturn(new WorkflowData([]));

        $request = new Request();

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setRequest($request);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        $this->assertEquals('/', $context->getResult()->getTargetUrl());
        $this->assertTrue($context->isProcessed());
    }

    public function testSkipNotSavedFormContext()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(false);

        $context->expects($this->never())->method('getResultType');

        $this->processor->process($context);
    }

    public function testSkipNotSupportedResultTypes()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(true);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $this->processor->process($context);
    }
}
