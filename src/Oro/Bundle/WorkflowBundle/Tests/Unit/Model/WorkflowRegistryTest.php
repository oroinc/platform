<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDefinitionRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $entityRepository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $managerRegistry;

    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    private $assembler;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var WorkflowRegistry */
    private $registry;

    protected function setUp()
    {
        $this->entityRepository
            = $this->getMockBuilder(WorkflowDefinitionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityRepository);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityManager);

        $this->assembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->setMethods(['assemble'])
            ->getMock();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = new WorkflowRegistry($this->managerRegistry, $this->assembler, $this->featureChecker);
    }

    protected function tearDown()
    {
        unset(
            $this->entityRepository,
            $this->managerRegistry,
            $this->entityManager,
            $this->configManager,
            $this->assembler,
            $this->featureChecker,
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

    public function testGetWorkflowEnabledFeature()
    {
        $workflowName = 'test_workflow';
        $workflow = $this->createWorkflow($workflowName);
        $workflowDefinition = $workflow->getDefinition();

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        // run twice to test cache storage inside registry
        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertAttributeEquals([$workflowName => $workflow], 'workflowByName', $this->registry);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "test_workflow" not found
     */
    public function testGetWorkflowDisabledFeature()
    {
        $workflowName = 'test_workflow';

        $this->entityRepository->expects($this->never())
            ->method('find')
            ->with($workflowName);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(false);

        $this->registry->getWorkflow($workflowName);
    }

    public function testGetWorkflowWithDbEntitiesUpdate()
    {
        $workflowName = 'test_workflow';
        $oldDefinition = new WorkflowDefinition();
        $oldDefinition->setName($workflowName)->setLabel('Old Workflow');
        $newDefinition = new WorkflowDefinition();
        $newDefinition->setName($workflowName)->setLabel('New Workflow');

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflow->setDefinition($oldDefinition);

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($oldDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($newDefinition));
        $this->prepareAssemblerMock($oldDefinition, $workflow);
        $this->setUpEntityManagerMock($oldDefinition, false);

        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertEquals($newDefinition, $workflow->getDefinition());
        $this->assertAttributeEquals([$workflowName => $workflow], 'workflowByName', $this->registry);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "test_workflow" not found
     */
    public function testGetWorkflowNoUpdatedEntity()
    {
        $workflowName = 'test_workflow';
        $workflow = $this->createWorkflow($workflowName);
        $workflowDefinition = $workflow->getDefinition();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue(null));
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition, false);

        $this->registry->getWorkflow($workflowName);
    }

    public function testHasActiveWorkflowsByEntityClassEnabledFeature()
    {
        $workflowName = 'test_workflow';
        $entityClass = 'testEntityClass';
        $workflow = $this->createWorkflow($workflowName, $entityClass);
        $workflowDefinition = $workflow->getDefinition();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with($entityClass)
            ->willReturn([$workflowDefinition]);

        $this->prepareAssemblerMock();

        $this->assertTrue($this->registry->hasActiveWorkflowsByEntityClass($entityClass));
    }

    public function testHasActiveWorkflowsByEntityClassDisabledFeature()
    {
        $workflowName = 'test_workflow';
        $entityClass = 'testEntityClass';
        $workflow = $this->createWorkflow($workflowName, $entityClass);
        $workflowDefinition = $workflow->getDefinition();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(false);

        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with($entityClass)
            ->willReturn([$workflowDefinition]);

        $this->prepareAssemblerMock();

        $this->assertFalse($this->registry->hasActiveWorkflowsByEntityClass($entityClass));
    }

    public function testGetActiveWorkflowsByEntityClass()
    {
        $entityClass = 'testEntityClass';
        $workflowName = 'test_workflow';
        $workflow = $this->createWorkflow($workflowName, $entityClass);
        $workflowDefinition = $workflow->getDefinition();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with($entityClass)
            ->willReturn([$workflowDefinition]);
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        $this->assertEquals(
            new ArrayCollection(['test_workflow' => $workflow]),
            $this->registry->getActiveWorkflowsByEntityClass($entityClass)
        );
    }

    public function testGetActiveWorkflowsByEntityClassWithDisabledFeature()
    {
        $entityClass = 'testEntityClass';

        $firstWorkflowName = 'first_test_workflow';
        $firstWorkflow = $this->createWorkflow($firstWorkflowName, $entityClass);
        $firstWorkflowDefinition = $firstWorkflow->getDefinition();

        $secondWorkflowName = 'second_test_workflow';
        $secondWorkflowDefinition = $firstWorkflow->getDefinition();

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                [$firstWorkflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY, null, true],
                [$secondWorkflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY, null, false]
            ]);

        $this->entityRepository->expects($this->once())
            ->method('findActiveForRelatedEntity')
            ->with($entityClass)
            ->willReturn([$firstWorkflowDefinition, $secondWorkflowDefinition]);

        $this->prepareAssemblerMock($firstWorkflowDefinition, $firstWorkflow);
        $this->setUpEntityManagerMock($firstWorkflowDefinition);

        $this->assertEquals(
            new ArrayCollection(['first_test_workflow' => $firstWorkflow]),
            $this->registry->getActiveWorkflowsByEntityClass($entityClass)
        );
    }

    /**
     * @param array $groups
     * @param array $activeDefinitions
     * @param array|Workflow[] $expectedWorkflows
     * @dataProvider getActiveWorkflowsByActiveGroupsDataProvider
     */
    public function testGetActiveWorkflowsByActiveGroups(
        array $groups,
        array $activeDefinitions,
        array $expectedWorkflows
    ) {
        foreach ($expectedWorkflows as $workflow) {
            $this->prepareAssemblerMock($workflow->getDefinition(), $workflow);
            $this->setUpEntityManagerMock($workflow->getDefinition());
        }

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($activeDefinitions);

        $this->assertEquals($expectedWorkflows, $this->registry->getActiveWorkflowsByActiveGroups($groups));
    }

    /**
     * @return array
     */
    public function getActiveWorkflowsByActiveGroupsDataProvider()
    {
        $workflow1 = $this->createWorkflow('test_workflow1', 'testEntityClass');
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setGroups([WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE => ['group1']]);

        $workflow2 = $this->createWorkflow('test_workflow2', 'testEntityClass');
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setGroups([WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE => ['group2', 'group3']]);

        return [
            'empty' => [
                'groups' => [],
                'activeDefinitions' => [],
                'expectedWorkflows' => [],
            ],
            'filled' => [
                'groups' => ['group1'],
                'activeDefinitions' => [$workflowDefinition1, $workflowDefinition2],
                'expectedWorkflows' => [$workflow1],
            ],
        ];
    }

    public function testGetActiveWorkflowsByActiveGroupsWithDisabledFeature()
    {
        $workflow1 = $this->createWorkflow('test_workflow1', 'testEntityClass');
        $workflowDefinition1 = $workflow1->getDefinition();
        $workflowDefinition1->setGroups([WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE => ['group1']]);

        $workflow2 = $this->createWorkflow('test_workflow2', 'testEntityClass');
        $workflowDefinition2 = $workflow2->getDefinition();
        $workflowDefinition2->setGroups([WorkflowDefinition::GROUP_TYPE_EXCLUSIVE_ACTIVE => ['group2', 'group3']]);

        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$workflowDefinition1, $workflowDefinition2]);

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(false);

        $this->assertEmpty($this->registry->getActiveWorkflowsByActiveGroups(['group1']));
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

    /**
     * @param string $workflowName
     *
     * @param string|null $relatedEntity
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createWorkflow($workflowName, $relatedEntity = null)
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        if ($relatedEntity) {
            $workflowDefinition->setRelatedEntity($relatedEntity);
        }

        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        return $workflow;
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "not_existing_workflow" not found
     */
    public function testGetWorkflowNotFoundException()
    {
        $workflowName = 'not_existing_workflow';

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($workflowName, WorkflowRegistry::FEATURE_CONFIG_WORKFLOW_KEY)
            ->willReturn(true);

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->willReturn(null);
        $this->prepareAssemblerMock();

        $this->registry->getWorkflow($workflowName);
    }
}
