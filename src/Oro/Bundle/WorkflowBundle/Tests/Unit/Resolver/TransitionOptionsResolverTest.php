<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Resolver;

use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionOptionsResolverTest extends TestCase
{
    private OptionsResolver&MockObject $optionsResolver;
    private TransitionOptionsResolver $transitionOptionsResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
        $this->transitionOptionsResolver = new TransitionOptionsResolver($this->optionsResolver);
    }

    public function testResolveTransitionOptions(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['option1' => 'value1']);
        $transition->expects($this->once())
            ->method('setFrontendOptions')
            ->with(['resolved option' => 'value']);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($workflowItem, ['option1' => 'value1'])
            ->willReturn(['resolved option' => 'value']);

        $this->transitionOptionsResolver->resolveTransitionOptions($transition, $workflowItem);
    }

    public function testResolveTransitionOptionsWithoutOptions(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $transition->expects($this->never())
            ->method('setFrontendOptions');

        $this->optionsResolver->expects($this->never())
            ->method('resolveOptions');

        $this->transitionOptionsResolver->resolveTransitionOptions($transition, $workflowItem);
    }
}
