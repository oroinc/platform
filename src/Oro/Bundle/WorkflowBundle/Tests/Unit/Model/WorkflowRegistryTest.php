<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilterInterface;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowRegistryTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'testEntityClass';
    const WORKFLOW_NAME = 'test_workflow';

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $assembler;

    /** @var WorkflowDefinitionFilters|\PHPUnit\Framework\MockObject\MockObject */
    private $filters;

    /** @var WorkflowDefinitionFilterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filter;

    /** @var WorkflowRegistry */
    private $registry;

    protected function setUp()
    {
        $this->entityRepository
            = $this->getMockBuilder(WorkflowDefinitionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityRepository);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityManager);

        $this->assembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->setMethods(['assemble'])
            ->getMock();

        $this->filters = $this->getMockBuilder(WorkflowDefinitionFilters::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = $this->createMock(WorkflowDefinitionFilterInterface::class);

        $this->registry = new WorkflowRegistry($this->doctrineHelper, $this->assembler, $this->filters);
    }

    protected function tearDown()
    {
        unset(
            $this->entityRepository,
            $this->doctrineHelper,
            $this->entityManager,
            $this->configManager,
            $this->assembler,
            $this->filters,
            $this->filter,
            $this->registry
        );
    }

    /**
     * @param WorkflowDefinition|null $workflowDefinition
     * @param Workflow|null $workflow
     */
    public function prepareAssemblerMock($workflowDefinition = null, $workflow = null)
    {
        if ($workflowDefinition && $workflow) {
            $this->assembler->expects($this->once())
                ->method('assemble')
                ->with($workflowDefinition)
                ->willReturn($workflow);
        } else {
            $this->assembler->expects($this->never())
                ->method('assemble');
        }
    }

    public function testGetWorkflowWithDbEntitiesUpdate()
    {
        $oldDefinition = new WorkflowDefinition();
        $oldDefinition->setName(self::WORKFLOW_NAME)->setLabel('Old Workflow');
        $newDefinition = new WorkflowDefinition();
        $newDefinition->setName(self::WORKFLOW_NAME)->setLabel('New Workflow');

        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflow->setDefinition($oldDefinition);

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->will($this->returnValue($oldDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->will($this->returnValue($newDefinition));
        $this->prepareAssemblerMock($oldDefinition, $workflow);
        $this->setUpEntityManagerMock($oldDefinition, false);

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection([$this->filter]));
        $this->filter->expects($this->once())->method('filter')
            ->with(new ArrayCollection([$oldDefinition]))->willReturn(new ArrayCollection([$oldDefinition]));

        $this->assertEquals($workflow, $this->registry->getWorkflow(self::WORKFLOW_NAME));
        $this->assertEquals($newDefinition, $workflow->getDefinition());
        $this->assertAttributeEquals([self::WORKFLOW_NAME => $workflow], 'workflowByName', $this->registry);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "test_workflow" not found
     */
    public function testGetWorkflowAndFilteredItem()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects($this->once())->method('find')
            ->with(self::WORKFLOW_NAME)->willReturn($workflowDefinition);

        $this->prepareAssemblerMock();

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection([$this->filter]));
        $this->filter->expects($this->once())->method('filter')
            ->with(new ArrayCollection([$workflowDefinition]))->willReturn(new ArrayCollection());

        $this->registry->getWorkflow(self::WORKFLOW_NAME);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "test_workflow" not found
     */
    public function testGetWorkflowNoUpdatedEntity()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->will($this->returnValue($workflowDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with(self::WORKFLOW_NAME)
            ->will($this->returnValue(null));
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition, false);

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection());

        $this->registry->getWorkflow(self::WORKFLOW_NAME);
    }

    public function testGetActiveWorkflowsByEntityClass()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection());

        $this->assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getActiveWorkflowsByEntityClass(self::ENTITY_CLASS)
        );
    }

    /**
     * @dataProvider hasWorkflowsByEntityClassDataProvider
     *
     * @param array $definitions
     * @param bool $expected
     */
    public function testHasActiveWorkflowsByEntityClass(array $definitions, $expected)
    {
        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn($definitions);
        $this->filters->expects($this->any())->method('getFilters')->willReturn(new ArrayCollection());

        $this->assertEquals($expected, $this->registry->hasActiveWorkflowsByEntityClass(self::ENTITY_CLASS));
    }

    public function testGetWorkflowsByEntityClass()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();

        $this->entityRepository->expects($this->once())
            ->method('findForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection());

        $this->assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getWorkflowsByEntityClass(self::ENTITY_CLASS)
        );
    }

    /**
     * @dataProvider hasWorkflowsByEntityClassDataProvider
     *
     * @param array $definitions
     * @param bool $expected
     */
    public function testHasWorkflowsByEntityClass(array $definitions, $expected)
    {
        $this->entityRepository->expects($this->once())
            ->method('findForRelatedEntity')
            ->with(self::ENTITY_CLASS)
            ->willReturn($definitions);
        $this->filters->expects($this->any())->method('getFilters')->willReturn(new ArrayCollection());

        $this->assertEquals($expected, $this->registry->hasWorkflowsByEntityClass(self::ENTITY_CLASS));
    }

    /**
     * @return array
     */
    public function hasWorkflowsByEntityClassDataProvider()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);

        return [
            'no workflows' => [
                'definitions' => [],
                'expected' => false,
            ],
            'with workflows' => [
                'definitions' => [$workflow],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $groups
     * @param array $activeDefinitions
     * @param array|Workflow[] $expectedWorkflows
     * @param WorkflowDefinitionFilterInterface $filter
     * @dataProvider getActiveWorkflowsByActiveGroupsDataProvider
     */
    public function testGetActiveWorkflowsByActiveGroups(
        array $groups,
        array $activeDefinitions,
        array $expectedWorkflows,
        WorkflowDefinitionFilterInterface $filter
    ) {
        foreach ($expectedWorkflows as $at => $workflow) {
            $this->assembler->expects($this->at($at))
                ->method('assemble')
                ->with($workflow->getDefinition())
                ->willReturn($workflow);
            $this->setUpEntityManagerMock($workflow->getDefinition());
        }

        $this->entityRepository->expects($this->once())
            ->method('findActive')
            ->willReturn($activeDefinitions);

        $this->filters->expects($this->any())->method('getFilters')->willReturn(new ArrayCollection([$filter]));

        $this->assertEquals(
            $expectedWorkflows,
            $this->registry->getActiveWorkflowsByActiveGroups($groups)->getValues()
        );
    }

    /**
     * @return array
     */
    public function getActiveWorkflowsByActiveGroupsDataProvider()
    {
        $workflow1 = $this->createWorkflow('test_workflow1', self::ENTITY_CLASS);
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setExclusiveActiveGroups(['group1']);
        $filter1 = $this->createDefinitionFilterMock(
            new ArrayCollection([]),
            new ArrayCollection([])
        );

        $filter2 = $this->createDefinitionFilterMock(
            new ArrayCollection(['test_workflow1' => $workflowDefinition1]),
            new ArrayCollection(['test_workflow1' => $workflowDefinition1])
        );

        $workflow2 = $this->createWorkflow('test_workflow2', self::ENTITY_CLASS);
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setExclusiveActiveGroups(['group2', 'group3']);
        $filter3 = $this->createDefinitionFilterMock(
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

    public function testGetActiveWorkflows()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();
        $workflowDefinition->setExclusiveActiveGroups(['group1']);

        $filter = $this->createDefinitionFilterMock(
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition]),
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition])
        );
        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection([$filter]));

        $this->entityRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->assertEquals(
            new ArrayCollection([self::WORKFLOW_NAME => $workflow]),
            $this->registry->getActiveWorkflows()
        );
    }

    public function testGetActiveWorkflowsNoFeature()
    {
        $workflow = $this->createWorkflow(self::WORKFLOW_NAME, self::ENTITY_CLASS);
        $workflowDefinition = $workflow->getDefinition();
        $workflowDefinition->setExclusiveActiveGroups(['group1']);

        $filter = $this->createDefinitionFilterMock(
            new ArrayCollection([self::WORKFLOW_NAME => $workflowDefinition]),
            new ArrayCollection([])
        );
        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection([$filter]));

        $this->entityRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$workflowDefinition]);

        $this->prepareAssemblerMock();

        $this->assertEquals(
            new ArrayCollection(),
            $this->registry->getActiveWorkflows()
        );
    }

    public function testGetActiveWorkflowsByActiveGroupsWithDisabledFeature()
    {
        $workflow1 = $this->createWorkflow('test_workflow1', self::ENTITY_CLASS);
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setExclusiveActiveGroups(['group1']);

        $workflow2 = $this->createWorkflow('test_workflow2', self::ENTITY_CLASS);
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setExclusiveActiveGroups(['group2', 'group3']);

        $this->entityRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$workflowDefinition1, $workflowDefinition2]);

        $filter = $this->createDefinitionFilterMock(
            new ArrayCollection(['test_workflow1' => $workflowDefinition1]),
            new ArrayCollection()
        );

        $this->filters->expects($this->once())->method('getFilters')->willReturn(new ArrayCollection([$filter]));

        $this->assertEmpty($this->registry->getActiveWorkflowsByActiveGroups(['group1']));
    }

    /**
     * @param Collection $in
     * @param Collection $out
     * @return WorkflowDefinitionFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDefinitionFilterMock(Collection $in, Collection $out)
    {
        $filter = $this->createMock(WorkflowDefinitionFilterInterface::class);
        $filter->expects($this->any())->method('filter')->with($in)->willReturn($out);

        return $filter;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param boolean $isEntityKnown
     */
    protected function setUpEntityManagerMock($workflowDefinition, $isEntityKnown = true)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->any())->method('isInIdentityMap')->with($workflowDefinition)
            ->will($this->returnValue($isEntityKnown));

        $this->entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
    }

    protected function setUpEntityManagerMockAllKnown()
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->any())->method('isInIdentityMap')->with()->willReturn(true);

        $this->entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
    }

    /**
     * @param string $workflowName
     *
     * @param string|null $relatedEntity
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWorkflow($workflowName, $relatedEntity = null)
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        if ($relatedEntity) {
            $workflowDefinition->setRelatedEntity($relatedEntity);
        }

        return $this->createWorkflowFromDefinition($workflowDefinition);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "not_existing_workflow" not found
     */
    public function testGetWorkflowNotFoundException()
    {
        $workflowName = 'not_existing_workflow';

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->willReturn(null);
        $this->prepareAssemblerMock();

        $this->registry->getWorkflow($workflowName);
    }

    /**
     * @param WorkflowDefinition $definition
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWorkflowFromDefinition(WorkflowDefinition $definition)
    {
        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn($definition->getName());

        return $workflow;
    }
}
