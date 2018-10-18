<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormStartHandleProcessor;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFromStartWorkflowDataProcessor;
use Symfony\Component\Form\FormInterface;

class DefaultFromStartWorkflowDataProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var  DefaultFormStartHandleProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new DefaultFromStartWorkflowDataProcessor();
    }

    public function testAddData()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['attribute_fields' => ['field_one' => '..', 'field_two' => '..']]);

        $toOverride = (object)['name' => 'to override'];
        $toPreserve = (object)['name' => 'to preserve'];
        $existent = (object)['name' => 'existent'];

        /** @var WorkflowData|\PHPUnit\Framework\MockObject\MockObject $workflowData */
        $workflowData = $this->createMock(WorkflowData::class);
        $workflowData->expects($this->once())
            ->method('getValues')
            ->with(['field_one', 'field_two'])
            ->willReturn(['field_one' => $toOverride, 'field_two' => $toPreserve]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($workflowData);

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setForm($form);
        $context->set(TransitionContext::INIT_DATA, ['field_one' => $existent]);

        $this->processor->process($context);

        $this->assertSame(
            [
                'field_one' => $existent,
                'field_two' => $toPreserve
            ],
            $context->get(TransitionContext::INIT_DATA)
        );
    }

    public function testCreatesInitData()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['attribute_fields' => ['field_two' => '..']]);

        $toPreserve = (object)['name' => 'to preserve'];

        /** @var WorkflowData|\PHPUnit\Framework\MockObject\MockObject $workflowData */
        $workflowData = $this->createMock(WorkflowData::class);
        $workflowData->expects($this->once())
            ->method('getValues')
            ->with(['field_two'])
            ->willReturn(['field_two' => $toPreserve]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($workflowData);

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setForm($form);
        $context->set(TransitionContext::INIT_DATA, null);

        $this->processor->process($context);

        $this->assertSame(
            ['field_two' => $toPreserve],
            $context->get(TransitionContext::INIT_DATA)
        );
    }
}
