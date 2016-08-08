<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowExclusiveRecordGroupFilter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

class WorkflowExclusiveRecordGroupFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var WorkflowExclusiveRecordGroupFilter
     */
    protected $exclusiveRecordGroupFilter;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->exclusiveRecordGroupFilter = new WorkflowExclusiveRecordGroupFilter($this->doctrineHelper);
    }

    /**
     * @param $expected
     * @param array $workflowItems
     * @param ArrayCollection $workflows
     * @dataProvider filterDataProvider
     */
    public function testFilter($expected, array $workflowItems, ArrayCollection $workflows)
    {
        $entity = new EntityStub(42);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')->with($entity)->willReturn(EntityStub::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')->willReturn(['id' => 42]);

        $workflowItemRepository = $this->getMockBuilder(WorkflowItemRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')
            ->with(WorkflowItem::class)->willReturn($workflowItemRepository);

        $workflowItemRepository->expects($this->once())
            ->method('findAllByEntityMetadata')
            ->with(EntityStub::class, ['id' => 42])
            ->willReturn($workflowItems);

        $this->assertEquals(
            $expected,
            $this->exclusiveRecordGroupFilter->filter($workflows, new WorkflowRecordContext($entity))
        );
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'foreign group' => $this->foreignGroupCase(),
            'same group' => $this->sameGroupCase(),
            'no group' => $this->noGroupCase(),
            'mixed' => $this->mixedCase()
        ];
    }

    protected function sameGroupCase()
    {
        $workflow1 = $this->getWorkflow('workflow1', ['group1']);
        $workflow2 = $this->getWorkflow('workflow2', ['group1']);

        $workflowItem1 = $this->getWorkflowItem('workflow1', ['group1']);

        return [
            new ArrayCollection([$workflow1]),
            [$workflowItem1],
            new ArrayCollection([$workflow1, $workflow2])
        ];
    }

    protected function foreignGroupCase()
    {
        $workflow1 = $this->getWorkflow('workflow1', ['group1']);
        $workflow2 = $this->getWorkflow('workflow2', ['group2']);

        $workflowItem1 = $this->getWorkflowItem('workflow1', ['group1']);

        return [
            new ArrayCollection([$workflow1, $workflow2]),
            [$workflowItem1],
            new ArrayCollection([$workflow1, $workflow2])
        ];
    }

    private function noGroupCase()
    {
        $workflow1 = $this->getWorkflow('workflow1', []);
        $workflow2 = $this->getWorkflow('workflow2', []);

        $workflowItem1 = $this->getWorkflowItem('workflow1', []);
        $workflowItem2 = $this->getWorkflowItem('workflow2', []);

        return [
            new ArrayCollection([$workflow1, $workflow2]),
            [$workflowItem1, $workflowItem2],
            new ArrayCollection([$workflow1, $workflow2])
        ];
    }


    private function mixedCase()
    {
        $workflow1 = $this->getWorkflow('w1', ['g1']);
        $workflow2 = $this->getWorkflow('w2', ['g2']);
        $workflow3 = $this->getWorkflow('w3', ['g3']);
        $workflow4 = $this->getWorkflow('w4', ['g2']);

        $workflowItem1 = $this->getWorkflowItem('w1', ['g1']);
        $workflowItem2 = $this->getWorkflowItem('w2', ['g2']);

        return [
            new ArrayCollection([$workflow1, $workflow2, $workflow3]),
            [$workflowItem1, $workflowItem2],
            new ArrayCollection([$workflow1, $workflow2, $workflow3, $workflow4])
        ];
    }

    /**
     * @param string $workflowName
     * @param array $exclusiveRecordGroups
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflow($workflowName, array $exclusiveRecordGroups)
    {
        $definition = new WorkflowDefinition();
        $definition->setName($workflowName);
        $definition->setGroups(
            [WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_RECORD => $exclusiveRecordGroups]
        );

        $workflowMock = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();

        $workflowMock->expects($this->any())->method('getName')->willReturn($workflowName);
        $workflowMock->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflowMock;
    }

    /**
     * @param string $workflowName
     * @param array $exclusiveRecordGroups
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflowItem($workflowName, array $exclusiveRecordGroups)
    {
        $definition = new WorkflowDefinition();
        $definition->setGroups(
            [
                WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_RECORD => $exclusiveRecordGroups
            ]
        );

        $workflowItemMock = $this->getMockBuilder(WorkflowItem::class)->disableOriginalConstructor()->getMock();

        $workflowItemMock->expects($this->any())->method('getWorkflowName')->willReturn($workflowName);
        $workflowItemMock->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $workflowItemMock;
    }
}
