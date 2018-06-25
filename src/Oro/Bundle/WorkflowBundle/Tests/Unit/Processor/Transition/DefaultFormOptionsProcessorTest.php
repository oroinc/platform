<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormOptionsProcessor;

class DefaultFormOptionsProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultFormOptionsProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new DefaultFormOptionsProcessor();
    }

    public function testSkipCustomForm()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('hasFormConfiguration')->willReturn(true);

        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('getTransition')->willReturn($transition);
        $context->expects($this->never())->method('getWorkflowItem');

        $this->processor->process($context);
    }

    public function testFormOptionsFill()
    {
        $formData = (object)['id' => 42];

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getData')->willReturn($formData);

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $transition->expects($this->once())->method('hasFormConfiguration')->willReturn(false);
        $transition->expects($this->once())->method('getName')->willReturn('transitionName');
        $transition->expects($this->once())->method('getFormOptions')->willReturn(['opt1' => 'val1']);

        $this->processor->process($context);

        $this->assertSame($formData, $context->getFormData());
        $this->assertSame(
            [
                'opt1' => 'val1',
                'workflow_item' => $workflowItem,
                'transition_name' => 'transitionName'
            ],
            $context->getFormOptions()
        );
    }
}
