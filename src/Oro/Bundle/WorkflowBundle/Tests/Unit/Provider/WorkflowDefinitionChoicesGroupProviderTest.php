<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionChoicesGroupProvider;

class WorkflowDefinitionChoicesGroupProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDefinitionChoicesGroupProvider */
    protected $choicesProvider;

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowDefinitionRepo;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowDefinitionRepo = $this->createMock(WorkflowDefinitionRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())->method('getRepository')->willReturn($this->workflowDefinitionRepo);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())->method('getManagerForClass')->willReturn($manager);

        $this->choicesProvider = new WorkflowDefinitionChoicesGroupProvider($managerRegistry);
    }

    /**
     * @dataProvider activeGroupsProvider
     *
     * @param array $workflows
     * @param mixed $expected
     */
    public function testGetActiveGroupsChoices(array $workflows, $expected)
    {
        $this->workflowDefinitionRepo->expects($this->any())
            ->method('findAll')->willReturn($workflows);

        $this->assertEquals($expected, $this->choicesProvider->getActiveGroupsChoices());
    }

    /**
     * @return \Generator
     */
    public function activeGroupsProvider()
    {
        $wd1 = $this->createWorkflowDefinition([], []);
        $wd2 = $this->createWorkflowDefinition([], ['group1', 'group2']);
        $wd3 = $this->createWorkflowDefinition([], ['group3', 'group2']);

        yield 'empty' => [
            'workflows' => [],
            'expected' => []
        ];

        yield 'one workflow' => [
            'workflows' => [$wd1],
            'expected' => []
        ];

        yield 'two workflows' => [
            'workflows' => [$wd1, $wd2],
            'expected' => ['group1' => 'group1', 'group2' => 'group2']
        ];

        yield 'three workflows' => [
            'workflows' => [$wd1, $wd2, $wd3],
            'expected' => ['group1' => 'group1', 'group2' => 'group2', 'group3' => 'group3']
        ];
    }

    /**
     * @dataProvider recordGroupsProvider
     *
     * @param array $workflows
     * @param mixed $expected
     */
    public function testGetRecordGroupsChoices(array $workflows, $expected)
    {
        $this->workflowDefinitionRepo->expects($this->any())
            ->method('findAll')->willReturn($workflows);

        $this->assertEquals($expected, $this->choicesProvider->getRecordGroupsChoices());
    }

    /**
     * @return \Generator
     */
    public function recordGroupsProvider()
    {
        $wd1 = $this->createWorkflowDefinition([], []);
        $wd2 = $this->createWorkflowDefinition(['group1', 'group2'], []);
        $wd3 = $this->createWorkflowDefinition(['group3', 'group2'], []);

        yield 'empty' => [
            'workflows' => [],
            'expected' => []
        ];

        yield 'one workflow' => [
            'workflows' => [$wd1],
            'expected' => []
        ];

        yield 'two workflows' => [
            'workflows' => [$wd1, $wd2],
            'expected' => ['group1' => 'group1', 'group2' => 'group2']
        ];

        yield 'three workflows' => [
            'workflows' => [$wd1, $wd2, $wd3],
            'expected' => ['group1' => 'group1', 'group2' => 'group2', 'group3' => 'group3']
        ];
    }

    /**
     * @param array $recordGroups
     * @param array $activeGroups
     *
     * @return WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWorkflowDefinition(array $recordGroups, array $activeGroups)
    {
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->any())->method('getExclusiveRecordGroups')
            ->willReturn($recordGroups);
        $workflowDefinition->expects($this->any())->method('getExclusiveActiveGroups')
            ->willReturn($activeGroups);

        return $workflowDefinition;
    }
}
