<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Resolver;

use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class TransitionOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsResolver;

    /** @var TransitionOptionsResolver */
    protected $transitionOptionsResolver;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
        $this->transitionOptionsResolver = new TransitionOptionsResolver($this->optionsResolver);
    }

    public function testResolveTransitionOptions()
    {
        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getFrontendOptions')
            ->willReturn(['option1' => 'value1']);
        $transition->expects($this->once())->method('setFrontendOptions')
            ->with(['resolved option' => 'value']);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($workflowItem, ['option1' => 'value1'])
            ->willReturn(['resolved option' => 'value']);

        $this->transitionOptionsResolver->resolveTransitionOptions($transition, $workflowItem);
    }

    public function testResolveTransitionOptionsWithoutOptions()
    {
        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getFrontendOptions')->willReturn([]);
        $transition->expects($this->never())->method('setFrontendOptions');

        $this->optionsResolver->expects($this->never())->method('resolveOptions');

        $this->transitionOptionsResolver->resolveTransitionOptions($transition, $workflowItem);
    }
}
