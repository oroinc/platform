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
use Symfony\Component\HttpFoundation\Request;

class FormSubmitLayoutRedirectProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormSubmitLayoutRedirectProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new FormSubmitLayoutRedirectProcessor();
    }

    public function testRedirectFromWorkflowItemResult()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
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
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn(new WorkflowData([]));

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
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn(new WorkflowData([]));

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
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn(new WorkflowData([]));

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
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('isSaved')
            ->willReturn(false);

        $context->expects($this->never())
            ->method('getResultType');

        $this->processor->process($context);
    }

    public function testSkipNotSupportedResultTypes()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('isSaved')->willReturn(true);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $this->processor->process($context);
    }
}
