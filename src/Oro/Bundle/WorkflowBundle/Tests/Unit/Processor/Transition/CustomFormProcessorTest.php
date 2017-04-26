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

class CustomFormProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormHandlerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $formHandlerRegistry;

    /** @var CustomFormProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->formHandlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->processor = new CustomFormProcessor($this->formHandlerRegistry);
    }

    public function testSaved()
    {
        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getFormHandler')->willReturn('formHandlerName');
        $transition->expects($this->once())->method('getFormDataAttribute')->willReturn('dataAttribute');

        $dataObject = (object)['id' => 42];

        /** @var WorkflowData|\PHPUnit_Framework_MockObject_MockObject $workflowData */
        $workflowData = $this->createMock(WorkflowData::class);
        $workflowData->expects($this->once())->method('get')->with('dataAttribute')->willReturn($dataObject);

        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getData')->willReturn($workflowData);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form $form */
        $form = $this->createMock(FormInterface::class);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);

        /** @var FormHandlerInterface|\PHPUnit_Framework_MockObject_MockObject $context */
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
