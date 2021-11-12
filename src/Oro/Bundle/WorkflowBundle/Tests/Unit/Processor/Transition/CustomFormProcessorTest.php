<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\CustomFormProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomFormProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $formHandlerRegistry;

    /** @var CustomFormProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->formHandlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->processor = new CustomFormProcessor($this->formHandlerRegistry);
    }

    public function testSaved()
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFormHandler')
            ->willReturn('formHandlerName');
        $transition->expects($this->once())
            ->method('getFormDataAttribute')
            ->willReturn('dataAttribute');

        $dataObject = (object)['id' => 42];

        $workflowData = $this->createMock(WorkflowData::class);
        $workflowData->expects($this->once())
            ->method('get')
            ->with('dataAttribute')
            ->willReturn($dataObject);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $form = $this->createMock(FormInterface::class);

        $request = $this->createMock(Request::class);

        $handler = $this->createMock(FormHandlerInterface::class);
        $handler->expects($this->once())
            ->method('process')
            ->with($dataObject, $form, $request)
            ->willReturn(true);

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);
        $context->setRequest($request);
        $context->setForm($form);

        $this->formHandlerRegistry->expects($this->once())
            ->method('get')
            ->with('formHandlerName')
            ->willReturn($handler);

        $this->assertFalse($context->isSaved());

        $this->processor->process($context);

        $this->assertTrue($context->isSaved());
    }
}
