<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Tests\Unit\Transition;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\StartInitDataProcessor;

class StartInitDataProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ButtonSearchContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $buttonContextProvider;

    /** @var StartInitDataProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->buttonContextProvider = $this->createMock(ButtonSearchContextProvider::class);

        $this->processor = new StartInitDataProcessor($this->buttonContextProvider);
    }

    public function testSkipContextWithoutInitOptions()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('isEmptyInitOptions')->willReturn(true);
        $transition->expects($this->never())->method('getInitContextAttribute');

        $context = new TransitionContext();
        $context->setTransition($transition);

        $this->processor->process($context);
    }

    public function addInitContextAttributeToInitData()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('isEmptyInitOptions')->willReturn(false);
        $transition->expects($this->once())->method('getInitContextAttribute')->willReturn('attribute');

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->set(TransitionContext::INIT_DATA, ['other data' => 42, 'attribute' => 'will be another']);

        /** @var ButtonSearchContext|\PHPUnit\Framework\MockObject\MockObject $buttonSearchContext */
        $buttonSearchContext = $this->createMock(ButtonSearchContext::class);

        $this->buttonContextProvider->expects($this->once())
            ->method('getButtonSearchContext')
            ->willReturn($buttonSearchContext);

        $this->processor->process($context);

        $this->assertSame(
            [
                'other data' => 42,
                'attribute' => $buttonSearchContext
            ],
            $context->get(TransitionContext::INIT_DATA)
        );

        $this->assertNull($context->get(TransitionContext::ENTITY_ID), 'Entity id must be nulled');
    }
}
