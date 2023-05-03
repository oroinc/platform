<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
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
    private FormSubmitLayoutRedirectProcessor $processor;

    protected function setUp(): void
    {
        $sameSiteUrlHelper = $this->createMock(SameSiteUrlHelper::class);
        $sameSiteUrlHelper->expects(self::any())
            ->method('getSameSiteReferer')
            ->with(self::isInstanceOf(Request::class))
            ->willReturnCallback(static function (Request $request) {
                return $request->headers->get('referer') ? $request->headers->get('referer') . '/safe' : '';
            });

        $this->processor = new FormSubmitLayoutRedirectProcessor($sameSiteUrlHelper);
    }

    public function testRedirectFromWorkflowItemResult(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects(self::any())
            ->method('getResult')
            ->willReturn(new WorkflowData(['redirectUrl' => '///workflow result url']));

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        self::assertEquals('///workflow result url', $context->getResult()->getTargetUrl());
        self::assertTrue($context->isProcessed());
    }

    public function testRedirectFromRequestHeadersReferer(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects(self::any())
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

        self::assertEquals('///referer url/safe', $context->getResult()->getTargetUrl());
        self::assertTrue($context->isProcessed());
    }

    public function testRedirectFromRequestOriginalUrl(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects(self::any())
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

        self::assertEquals('///original url', $context->getResult()->getTargetUrl());
        self::assertTrue($context->isProcessed());
    }

    public function testRedirectToRootAsDefaultIfAnythingDefined(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects(self::any())
            ->method('getResult')
            ->willReturn(new WorkflowData([]));

        $request = new Request();

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setRequest($request);
        $context->setSaved(true);
        $context->setResultType(new LayoutDialogResultType('route_name'));

        $this->processor->process($context);

        self::assertEquals('/', $context->getResult()->getTargetUrl());
        self::assertTrue($context->isProcessed());
    }

    public function testSkipNotSavedFormContext(): void
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('isSaved')
            ->willReturn(false);

        $context->expects(self::never())
            ->method('getResultType');

        $this->processor->process($context);
    }

    public function testSkipNotSupportedResultTypes(): void
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('isSaved')->willReturn(true);
        $context->expects(self::once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $this->processor->process($context);
    }
}
