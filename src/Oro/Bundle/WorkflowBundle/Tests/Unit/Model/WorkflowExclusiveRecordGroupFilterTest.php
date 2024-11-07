<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowExclusiveRecordGroupFilter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Provider\RunningWorkflowProvider;

class WorkflowExclusiveRecordGroupFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var RunningWorkflowProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $runningWorkflowProvider;

    /** @var WorkflowExclusiveRecordGroupFilter */
    private $exclusiveRecordGroupFilter;

    #[\Override]
    protected function setUp(): void
    {
        $this->runningWorkflowProvider = $this->createMock(RunningWorkflowProvider::class);

        $this->exclusiveRecordGroupFilter = new WorkflowExclusiveRecordGroupFilter($this->runningWorkflowProvider);
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(ArrayCollection $expected, array $workflowNames, ArrayCollection $workflows): void
    {
        $entity = new \stdClass();

        $this->runningWorkflowProvider->expects(self::once())
            ->method('getRunningWorkflowNames')
            ->with(self::identicalTo($entity))
            ->willReturn($workflowNames);

        self::assertEquals(
            $expected,
            $this->exclusiveRecordGroupFilter->filter($workflows, new WorkflowRecordContext($entity))
        );
    }

    public function filterDataProvider(): array
    {
        return [
            'foreign group' => $this->foreignGroupCase(),
            'same group' => $this->sameGroupCase(),
            'items with same group are covered by priority' => $this->itemsWithSameGroupsCase(),
            'no group' => $this->noGroupCase(),
            'mixed' => $this->mixedCase(),
            'unordered records' => $this->unorderedRecordsCase()
        ];
    }

    private function sameGroupCase(): array
    {
        $workflow1 = $this->getWorkflow('workflow1', ['group1']);
        $workflow2 = $this->getWorkflow('workflow2', ['group1']);

        return [
            'expected' => new ArrayCollection(['workflow1' => $workflow1]),
            'records' => [$workflow1->getName()],
            'active workflows' => new ArrayCollection(['workflow1' => $workflow1, 'workflow2' => $workflow2])
        ];
    }

    private function foreignGroupCase(): array
    {
        $workflow1 = $this->getWorkflow('workflow1', ['group1']);
        $workflow2 = $this->getWorkflow('workflow2', ['group2']);

        return [
            'expected' => new ArrayCollection(['workflow1' => $workflow1, 'workflow2' => $workflow2]),
            'records' => [$workflow1->getName()],
            'active workflwos' => new ArrayCollection(['workflow1' => $workflow1, 'workflow2' => $workflow2])
        ];
    }

    private function noGroupCase(): array
    {
        $workflow1 = $this->getWorkflow('workflow1', []);
        $workflow2 = $this->getWorkflow('workflow2', []);

        return [
            'expected' => new ArrayCollection(['workflow1' => $workflow1, 'workflow2' => $workflow2]),
            'records' => [$workflow1->getName(), $workflow2->getName()],
            'active workflows' => new ArrayCollection(['workflow1' => $workflow1, 'workflow2' => $workflow2])
        ];
    }

    private function mixedCase(): array
    {
        $workflow1 = $this->getWorkflow('w1', ['g1']);
        $workflow2 = $this->getWorkflow('w2', ['g2']);
        $workflow3 = $this->getWorkflow('w3', ['g3']);
        $workflow4 = $this->getWorkflow('w4', ['g2']);

        return [
            'expected' => new ArrayCollection(['w1' => $workflow1, 'w2' => $workflow2, 'w3' => $workflow3]),
            'records' => [$workflow1->getName(), $workflow2->getName()],
            'active workflows' => new ArrayCollection(
                ['w1' => $workflow1, 'w2' => $workflow2, 'w3' => $workflow3, 'w4' => $workflow4]
            )
        ];
    }

    private function unorderedRecordsCase(): array
    {
        $workflow1 = $this->getWorkflow('w1', ['g1']);
        $workflow2 = $this->getWorkflow('w2', ['g1']);

        return [
            'expected' => new ArrayCollection(['w2' => $workflow2]),
            'records' => [$workflow2->getName()],
            'active workflows' => new ArrayCollection(['w1' => $workflow1, 'w2' => $workflow2])
        ];
    }

    private function itemsWithSameGroupsCase(): array
    {
        $workflow1 = $this->getWorkflow('w1', ['g1', 'g2']);
        $workflow2 = $this->getWorkflow('w2', ['g1', 'g3']);

        return [
            'expected' => new ArrayCollection(['w1' => $workflow1]),
            'records' => [$workflow1->getName(), $workflow2->getName()],
            'active workflows' => new ArrayCollection(['w1' => $workflow1, 'w2' => $workflow2])
        ];
    }

    private function getWorkflow(string $workflowName, array $exclusiveRecordGroups): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setName($workflowName);
        $definition->setExclusiveRecordGroups($exclusiveRecordGroups);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getName')
            ->willReturn($workflowName);
        $workflow->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }
}
