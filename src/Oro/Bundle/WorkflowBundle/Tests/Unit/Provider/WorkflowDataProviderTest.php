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
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Provider\TransitionDataProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDataProvider;

class WorkflowDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDataProvider */
    private $workflowDataProvider;

    /** @var TransitionDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionDataProvider;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $systemWorkflowManager;

    protected function setUp(): void
    {
        $this->transitionDataProvider = $this->createMock(TransitionDataProvider::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->systemWorkflowManager = $this->createMock(WorkflowManager::class);

        $workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturnMap([
                [null, $this->systemWorkflowManager],
                ['default', $this->workflowManager],
            ]);

        $this->workflowDataProvider = new WorkflowDataProvider(
            $workflowManagerRegistry,
            $this->transitionDataProvider
        );
    }

    public function testWorkflowDataWithNoAvailableWorkflow()
    {
        $entity = new \stdClass();

        $workflow = $this->getWorkflow();
        $workflow->expects($this->atLeastOnce())
            ->method('getLabel')
            ->willReturn('label1');
        $workflow->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('name1');

        $this->transitionDataProvider->expects($this->never())
            ->method('getAvailableStartTransitionsData');

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->willReturn([]);
        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn(null);

        $this->workflowDataProvider->getWorkflowData($entity, $workflow, true);
    }

    public function testBasicWorkflowData()
    {
        $entity = new \stdClass();

        $workflow = $this->getWorkflow();
        $workflow->expects($this->atLeastOnce())
            ->method('getLabel')
            ->willReturn('label1');
        $workflow->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('name1');

        $this->transitionDataProvider->expects($this->once())
            ->method('getAvailableStartTransitionsData')
            ->willReturn([]);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->willReturn([$workflow]);
        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn(null);

        $data = $this->workflowDataProvider->getWorkflowData($entity, $workflow, true);
        $this->assertSame($data['name'], 'name1');
        $this->assertSame($data['label'], 'label1');
        $this->assertFalse($data['isStarted']);
    }

    public function testWhenWorkflowIsStarted()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflow = $this->getWorkflow();

        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);
        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn([$workflow]);

        $this->transitionDataProvider->expects($this->once())
            ->method('getAvailableTransitionsDataByWorkflowItem')
            ->with($workflowItem)
            ->willReturn([]);

        $data = $this->workflowDataProvider->getWorkflowData(new \stdClass(), $workflow, true);
        $this->assertTrue($data['isStarted']);
    }

    public function testStepDataWhenIssetCurrentStep()
    {
        $workflowStep = $this->createMock(WorkflowStep::class);
        $workflowStep->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('name1');

        $step = $this->createMock(Step::class);
        $step->expects($this->once())
            ->method('getLabel')
            ->willReturn('label_for_step_1');
        $step->expects($this->any())
            ->method('getAllowedTransitions')
            ->willReturn([]);

        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->once())
            ->method('getStep')
            ->with('name1')
            ->willReturn($step);
        $stepManager->expects($this->any())
            ->method('getOrderedSteps')
            ->willReturn($this->getCollections());

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);

        $workflow = $this->getWorkflow(null, $stepManager);

        $this->systemWorkflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);
        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn([$workflow]);

        $data = $this->workflowDataProvider->getWorkflowData(new \stdClass(), $workflow, true);
        $steps = $data['stepsData']['steps'];

        $this->assertCount(1, $steps);
        $this->assertSame($steps[0]['label'], 'label_for_step_1');
    }

    /**
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWorkflow(
        WorkflowDefinition $workflowDefinition = null,
        StepManager $stepManager = null
    ) {
        if ($workflowDefinition === null) {
            $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        }

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn($stepManager);

        return $workflow;
    }

    private function getCollections(): Collection
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('toArray')
            ->willReturn([]);
        $collection->expects($this->any())
            ->method('filter')
            ->willReturn(clone $collection);

        return $collection;
    }
}
