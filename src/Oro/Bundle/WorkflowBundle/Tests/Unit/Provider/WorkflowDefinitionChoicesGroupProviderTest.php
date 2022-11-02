<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionChoicesGroupProvider;

class WorkflowDefinitionChoicesGroupProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowDefinitionRepo;

    /** @var WorkflowDefinitionChoicesGroupProvider */
    private $choicesProvider;

    protected function setUp(): void
    {
        $this->workflowDefinitionRepo = $this->createMock(WorkflowDefinitionRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->workflowDefinitionRepo);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->choicesProvider = new WorkflowDefinitionChoicesGroupProvider($managerRegistry);
    }

    /**
     * @dataProvider activeGroupsProvider
     */
    public function testGetActiveGroupsChoices(array $workflows, array $expected)
    {
        $this->workflowDefinitionRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($workflows);

        $this->assertEquals($expected, $this->choicesProvider->getActiveGroupsChoices());
    }

    public function activeGroupsProvider(): array
    {
        $wd1 = $this->createWorkflowDefinition([], []);
        $wd2 = $this->createWorkflowDefinition([], ['group1', 'group2']);
        $wd3 = $this->createWorkflowDefinition([], ['group3', 'group2']);

        return [
            'empty' => [
                'workflows' => [],
                'expected' => []
            ],
            'one workflow' => [
                'workflows' => [$wd1],
                'expected' => []
            ],
            'two workflows' => [
                'workflows' => [$wd1, $wd2],
                'expected' => ['group1' => 'group1', 'group2' => 'group2']
            ],
            'three workflows' => [
                'workflows' => [$wd1, $wd2, $wd3],
                'expected' => ['group1' => 'group1', 'group2' => 'group2', 'group3' => 'group3']
            ]
        ];
    }

    /**
     * @dataProvider recordGroupsProvider
     */
    public function testGetRecordGroupsChoices(array $workflows, array $expected)
    {
        $this->workflowDefinitionRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($workflows);

        $this->assertEquals($expected, $this->choicesProvider->getRecordGroupsChoices());
    }

    public function recordGroupsProvider(): array
    {
        $wd1 = $this->createWorkflowDefinition([], []);
        $wd2 = $this->createWorkflowDefinition(['group1', 'group2'], []);
        $wd3 = $this->createWorkflowDefinition(['group3', 'group2'], []);

        return [
            'empty' => [
                'workflows' => [],
                'expected' => []
            ],
            'one workflow' => [
                'workflows' => [$wd1],
                'expected' => []
            ],
            'two workflows' => [
                'workflows' => [$wd1, $wd2],
                'expected' => ['group1' => 'group1', 'group2' => 'group2']
            ],
            'three workflows' => [
                'workflows' => [$wd1, $wd2, $wd3],
                'expected' => ['group1' => 'group1', 'group2' => 'group2', 'group3' => 'group3']
            ]
        ];
    }

    private function createWorkflowDefinition(array $recordGroups, array $activeGroups): WorkflowDefinition
    {
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->any())
            ->method('getExclusiveRecordGroups')
            ->willReturn($recordGroups);
        $workflowDefinition->expects($this->any())
            ->method('getExclusiveActiveGroups')
            ->willReturn($activeGroups);

        return $workflowDefinition;
    }
}
