<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\TransitionOptionsResolveProcessor;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class TransitionOptionsResolveProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $resolver = $this->createMock(TransitionOptionsResolver::class);

        $transition = $this->createMock(Transition::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $context->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $resolver->expects($this->once())
            ->method('resolveTransitionOptions')
            ->with($transition, $workflowItem);

        $processor = new TransitionOptionsResolveProcessor($resolver);
        $processor->process($context);
    }
}
