<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowRegistryTest extends TestCase
{
    private const ENTITY_CLASS = 'testEntityClass';
    private const WORKFLOW_NAME = 'test_workflow';

    private WorkflowDefinitionRepository&MockObject $entityRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private WorkflowAssembler&MockObject $assembler;
    private WorkflowDefinitionFilters&MockObject $filters;
    private WorkflowDefinitionFilterInterface&MockObject $filter;
    private WorkflowRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(WorkflowDefinitionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->assembler = $this->createMock(WorkflowAssembler::class);
        $this->filters = $this->createMock(WorkflowDefinitionFilters::class);
        $this->filter = $this->createMock(WorkflowDefinitionFilterInterface::class);

        $this->entityManager->expects(self::any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityRepository);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityManager);

        $this->registry = new WorkflowRegistry($doctrineHelper, $this->assembler, $this->filters);
    }

    private function prepareAssemblerMock(
        ?WorkflowDefinition $workflowDefinition = null,
        ?Workflow $workflow = null
    ): void {
        if ($workflowDefinition && $workflow) {
            $this->assembler->expects(self::once())
                ->method('assemble')
                ->with($workflowDefinition)
                ->willReturn($workflow);
        } else {
            $this->assembler->expects(self::never())
                ->method('assemble');
        }
    }

    public function testGetWorkflowWithDbEntitiesUpdate(): void
    {
        $oldDefinition = new WorkflowDefinition();
        $oldDefinition->setName(self::WORKFLOW_NAME)->setLabel('Old Workflow');
        $newDefinition = new WorkflowDefinition();
        $newDefinition->setName(self::WORKFLOW_NAME)->setLabel('New Workflow');

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $workflow->setDefinition($oldDefinition);

        $this->entityRepository->expects(self::exactly(2))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->willReturnOnConsecutiveCalls($oldDefinition, $newDefinition);
        $this->prepareAssemblerMock($oldDefinition, $workflow);
        $this->setUpEntityManagerMock($oldDefinition, false);

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$this->filter]));
        $this->filter->expects(self::once())
            ->method('filter')
            ->with(new ArrayCollection([$oldDefinition]))
            ->willReturn(new ArrayCollection([$oldDefinition]));

        self::assertEquals($workflow, $this->registry->getWorkflow(self::WORKFLOW_NAME));
        self::assertEquals($newDefinition, $workflow->getDefinition());
        self::assertEquals(
            [self::WORKFLOW_NAME => $workflow],
            ReflectionUtil::getPropertyValue($this->registry, 'workflowByName')
        );
    }

    public function testGetWorkflowAndFilteredItem(): void
    {
        $this->expectException(WorkflowNotFoundException::class);
        $this->expectExceptionMessage('Workflow "test_workflow" not found');

        $workflow = $this->createWorkflow(self::WORKFLOW_NAME);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects(self::once())
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($workflowDefinition);

        $this->prepareAssemblerMock();

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$this->filter]));
        $this->filter->expects(self::once())
            ->method('filter')
            ->with(new ArrayCollection([$workflowDefinition]))
            ->willReturn(new ArrayCollection());

        $this->registry->getWorkflow(self::WORKFLOW_NAME);
    }

    public function testGetWorkflowNoUpdatedEntity(): void
    {
        $this->expectException(WorkflowNotFoundException::class);
        $this->expectExceptionMessage('Workflow "test_workflow" not found');

        $workflow = $this->createWorkflow(self::WORKFLOW_NAME);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects(self::exactly(2))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->willReturnOnConsecutiveCalls($workflowDefinition, null);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition, false);

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection());

        $this->registry->getWorkflow(self::WORKFLOW_NAME);
    }

    public function testGetActiveWorkflowsByEntityClass(): void
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects(self::once())
            ->method('findActiveForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection());

        self::assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getActiveWorkflowsByEntityClass(self::ENTITY_CLASS)
        );
    }

    /**
     * @dataProvider hasWorkflowsByEntityClassDataProvider
     */
    public function testHasActiveWorkflowsByEntityClass(array $definitions, bool $expected): void
    {
        $this->entityRepository->expects(self::once())
            ->method('findActiveForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn($definitions);
        $this->filters->expects(self::any())
            ->method('getFilters')
            ->willReturn(new ArrayCollection());

        self::assertEquals($expected, $this->registry->hasActiveWorkflowsByEntityClass(self::ENTITY_CLASS));
    }

    public function testGetWorkflowsByEntityClass(): void
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects(self::once())
            ->method('findForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection());

        self::assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getWorkflowsByEntityClass(self::ENTITY_CLASS)
        );
    }

    /**
     * @dataProvider hasWorkflowsByEntityClassDataProvider
     */
    public function testHasWorkflowsByEntityClass(array $definitions, bool $expected): void
    {
        $this->entityRepository->expects(self::once())
            ->method('findForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn($definitions);
        $this->filters->expects(self::any())
            ->method('getFilters')
            ->willReturn(new ArrayCollection());

        self::assertEquals($expected, $this->registry->hasWorkflowsByEntityClass(self::ENTITY_CLASS));
    }

    public function hasWorkflowsByEntityClassDataProvider(): array
    {
        return [
            'no workflows' => [
                'definitions' => [],
                'expected' => false,
            ],
            'with workflows' => [
                'definitions' => [$this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS)],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider getActiveWorkflowsByActiveGroupsDataProvider
     */
    public function testGetActiveWorkflowsByActiveGroups(
        array $groups,
        array $activeDefinitions,
        array $expectedWorkflows,
        WorkflowDefinitionFilterInterface $filter
    ): void {
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $workflowDefinitionExpectations = [];
        foreach ($expectedWorkflows as $workflow) {
            $workflowDefinitionExpectations[] = [$workflow->getDefinition()];
        }
        $this->assembler->expects(self::exactly(count($expectedWorkflows)))
            ->method('assemble')
            ->withConsecutive(...$workflowDefinitionExpectations)
            ->willReturnOnConsecutiveCalls(...$expectedWorkflows);
        $unitOfWork->expects(self::exactly(count($expectedWorkflows)))
            ->method('isInIdentityMap')
            ->withConsecutive(...$workflowDefinitionExpectations)
            ->willReturn(true);

        $this->entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->entityRepository->expects(self::once())
            ->method('findActive')
            ->willReturn($activeDefinitions);

        $this->filters->expects(self::any())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$filter]));

        self::assertEquals(
            $expectedWorkflows,
            $this->registry->getActiveWorkflowsByActiveGroups($groups)->getValues()
        );
    }

    public function getActiveWorkflowsByActiveGroupsDataProvider(): array
    {
        $workflow1 = $this->createWorkflow('test_workflow1', self::ENTITY_CLASS);
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setExclusiveActiveGroups(['group1']);
        $filter1 = $this->createDefinitionFilter(
            new ArrayCollection([]),
            new ArrayCollection([])
        );

        $filter2 = $this->createDefinitionFilter(
            new ArrayCollection(['test_workflow1' => $workflowDefinition1]),
            new ArrayCollection(['test_workflow1' => $workflowDefinition1])
        );

        $workflow2 = $this->createWorkflow('test_workflow2', self::ENTITY_CLASS);
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setExclusiveActiveGroups(['group2', 'group3']);
        $filter3 = $this->createDefinitionFilter(
            new ArrayCollection(['test_workflow1' => $workflowDefinition1]),
            new ArrayCollection([])
        );

        return [
            'empty' => [
                'groups' => [],
                'activeDefinitions' => [],
                'expectedWorkflows' => [],
                'filter' => $filter1
            ],
            'filled' => [
                'groups' => ['group1'],
                'activeDefinitions' => [$workflowDefinition1, $workflowDefinition2],
                'expectedWorkflows' => [$workflow1],
                'filter' => $filter2
            ],
            'filtered' => [
                'groups' => ['group1'],
                'activeDefinitions' => [$workflowDefinition1, $workflowDefinition2],
                'expectedWorkflows' => [],
                'filter' => $filter3
            ]
        ];
    }

    public function testGetActiveWorkflows(): void
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();
        $workflowDefinition->setExclusiveActiveGroups(['group1']);

        $filter = $this->createDefinitionFilter(
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition]),
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition])
        );
        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$filter]));

        $this->entityRepository->expects(self::once())
            ->method('findActive')
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        self::assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getActiveWorkflows()
        );
    }

    public function testGetActiveWorkflowsNoFeature(): void
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();
        $workflowDefinition->setExclusiveActiveGroups(['group1']);

        $filter = $this->createDefinitionFilter(
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition]),
            new ArrayCollection([])
        );
        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$filter]));

        $this->entityRepository->expects(self::once())
            ->method('findActive')
            ->willReturn([$workflowDefinition]);

        $this->prepareAssemblerMock();

        self::assertEquals(
            new ArrayCollection(),
            $this->registry->getActiveWorkflows()
        );
    }

    public function testGetActiveWorkflowsByActiveGroupsWithDisabledFeature(): void
    {
        $workflow1 = $this->createWorkflow('test_workflow1', self::ENTITY_CLASS);
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setExclusiveActiveGroups(['group1']);

        $workflow2 = $this->createWorkflow('test_workflow2', self::ENTITY_CLASS);
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setExclusiveActiveGroups(['group2', 'group3']);

        $this->entityRepository->expects(self::once())
            ->method('findActive')
            ->willReturn([$workflowDefinition1, $workflowDefinition2]);

        $filter = $this->createDefinitionFilter(
            new ArrayCollection(['test_workflow1' => $workflowDefinition1]),
            new ArrayCollection()
        );

        $this->filters->expects(self::once())
            ->method('getFilters')
            ->willReturn(new ArrayCollection([$filter]));

        self::assertEmpty($this->registry->getActiveWorkflowsByActiveGroups(['group1']));
    }

    private function createDefinitionFilter(Collection $in, Collection $out): WorkflowDefinitionFilterInterface
    {
        $filter = $this->createMock(WorkflowDefinitionFilterInterface::class);
        $filter->expects(self::any())
            ->method('filter')
            ->with($in)
            ->willReturn($out);

        return $filter;
    }

    private function setUpEntityManagerMock(WorkflowDefinition $workflowDefinition, bool $isEntityKnown = true): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::any())
            ->method('isInIdentityMap')
            ->with($workflowDefinition)
            ->willReturn($isEntityKnown);

        $this->entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
    }

    private function createWorkflow(string $workflowName, ?string $relatedEntity = null): Workflow
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        if ($relatedEntity) {
            $workflowDefinition->setRelatedEntity($relatedEntity);
        }

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects(self::any())
            ->method('getName')
            ->willReturn($workflowDefinition->getName());

        return $workflow;
    }

    public function testGetWorkflowNotFoundException(): void
    {
        $this->expectException(WorkflowNotFoundException::class);
        $this->expectExceptionMessage('Workflow "not_existing_workflow" not found');

        $workflowName = 'not_existing_workflow';

        $this->entityRepository->expects(self::once())
            ->method('find')
            ->with($workflowName)
            ->willReturn(null);
        $this->prepareAssemblerMock();

        $this->registry->getWorkflow($workflowName);
    }
}
