<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var TransitionButtonProviderExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new TransitionButtonProviderExtension($this->workflowRegistry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflowRegistry, $this->extension);
    }

    public function testFind()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn([$workflow]);

        $transitionManager = $this->getMock(TransitionManager::class);

        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $transition1 = (new Transition())->setName('transition1');
        $transition2 = (new Transition())->setName('transition2');
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn([$transition1, $transition2]);

        $buttons = [
            new TransitionButton($transition1, $workflow, new ButtonContext()),
            new TransitionButton($transition2, $workflow, new ButtonContext()),
        ];

        $this->assertEquals($buttons, $this->extension->find(new ButtonSearchContext()));
    }
}
