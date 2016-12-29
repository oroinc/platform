<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDataProvider;

class WorkflowDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDataProvider */
    private $workflowDataProvider;

    /** @var TransitionDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $transitionDataProvider;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transitionDataProvider = $this->getMockBuilder(TransitionDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowDataProvider = new WorkflowDataProvider($this->workflowManager, $this->transitionDataProvider);
    }

    protected function tearDown()
    {
        unset($this->transitionDataProvider, $this->workflowManager, $this->workflowDataProvider);
    }

    public function testBasicWorkflowData()
    {
        $workflow = $this->getWorkflow();
        $workflow->expects($this->atLeastOnce())->method('getLabel')->willReturn('label1');
        $workflow->expects($this->atLeastOnce())->method('getName')->willReturn('name1');

        $this->transitionDataProvider->expects($this->once())
            ->method('getAvailableStartTransitionsData')
            ->willReturn([]);

        $data = $this->workflowDataProvider->getWorkflowData(new \stdClass(), $workflow, true);
        $this->assertSame($data['name'], 'name1');
        $this->assertSame($data['label'], 'label1');
        $this->assertFalse($data['isStarted']);
    }

    public function testWhenWorkflowIsStarted()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->workflowManager->expects($this->once())->method('getWorkflowItem')->willReturn($workflowItem);

        $this->transitionDataProvider->expects($this->once())
            ->method('getAvailableTransitionsDataByWorkflowItem')
            ->with($workflowItem)
            ->willReturn([]);

        $workflow = $this->getWorkflow();

        $data = $this->workflowDataProvider->getWorkflowData(new \stdClass(), $workflow, true);
        $this->assertTrue($data['isStarted']);
    }

    public function testStepDataWhenIssetCurrentStep()
    {
        $workflowStep = $this->createMock(WorkflowStep::class);
        $workflowStep->expects($this->atLeastOnce())
            ->method('getName')->willReturn('name1');

        $step = $this->createMock(Step::class);
        $step->expects($this->once())
            ->method('getLabel')
            ->willReturn('label_for_step_1');
        $step->expects($this->any())
            ->method('getAllowedTransitions')->willReturn([]);

        $stepManager = $this->getMockBuilder(StepManager::class)->disableOriginalConstructor()->getMock();
        $stepManager->expects($this->once())->method('getStep')
            ->with('name1')->willReturn($step);
        $stepManager->expects($this->any())
            ->method('getOrderedSteps')->willReturn(
                $this->getCollections()
            );

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())->method('getCurrentStep')
            ->willReturn($workflowStep);

        $this->workflowManager->expects($this->once())->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $workflow = $this->getWorkflow(null, $stepManager);

        $data = $this->workflowDataProvider->getWorkflowData(new \stdClass(), $workflow, true);
        $steps = $data['stepsData']['steps'];

        $this->assertCount(1, $steps);
        $this->assertSame($steps[0]['label'], 'label_for_step_1');
    }

    /**
     * @param null|\PHPUnit_Framework_MockObject_MockObject|WorkflowDefinition $workflowDefinition
     * @param null|\PHPUnit_Framework_MockObject_MockObject|StepManager $stepManager
     *
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWorkflow($workflowDefinition = null, $stepManager = null)
    {
        if ($workflowDefinition === null) {
            $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        }

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('getDefinition')->willReturn($workflowDefinition);
        $workflow->expects($this->any())->method('getStepManager')->willReturn($stepManager);

        return $workflow;
    }

    /**
     * @return Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCollections()
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())->method('toArray')->willReturn([]);
        $collection->expects($this->any())->method('filter')->willReturn(clone $collection);

        return $collection;
    }
}
