<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Scope;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const FIELD_NAME = 'testField';
    const ENTITY_CLASS = 'stdClass';
    const ENTITY_ID = 42;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeManager;

    /** @var WorkflowScopeManager */
    private $workflowScopeManager;

    protected function setUp()
    {
        $this->repository = $this->getMock(ObjectRepository::class);

        $this->manager = $this->getMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        $registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    [self::ENTITY_CLASS, $this->manager],
                    [WorkflowDefinition::class, $this->manager]
                ]
            );

        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)->disableOriginalConstructor()->getMock();

        $this->workflowScopeManager = new WorkflowScopeManager($registry, $this->scopeManager);
    }

    protected function tearDown()
    {
        unset($this->workflowScopeManager, $this->repository, $this->manager, $this->scopeManager);
    }

    public function testUpdateScopes()
    {
        $scope1 = $this->createScope(101);
        $scope2 = $this->createScope(102);

        $definition = $this->createWorkflowDefinition(
            [[self::FIELD_NAME => self::ENTITY_ID], ['extraField' => self::ENTITY_ID]],
            [$this->createScope(100), $this->createScope(101)]
        );
        $entity = $this->createEntity(self::ENTITY_ID);

        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowScopeManager::SCOPE_TYPE)
            ->willReturn(
                [
                    'extraField' => self::ENTITY_CLASS,
                    self::FIELD_NAME => self::ENTITY_CLASS
                ]
            );
        $this->scopeManager->expects($this->exactly(2))
            ->method('findOrCreate')
            ->willReturnMap(
                [
                    [WorkflowScopeManager::SCOPE_TYPE, [self::FIELD_NAME => $entity], $scope1],
                    [WorkflowScopeManager::SCOPE_TYPE, ['extraField' => $entity], $scope2]
                ]
            );

        $this->repository->expects($this->exactly(2))->method('find')->with(self::ENTITY_ID)->willReturn($entity);
        $this->manager->expects($this->once())->method('flush');

        $this->workflowScopeManager->updateScopes($definition);

        $this->assertEquals([$scope1, $scope2], array_values($definition->getScopes()->toArray()));
    }

    public function testUpdateScopesWhenDisabled()
    {
        $this->scopeManager->expects($this->never())->method($this->anything());
        $this->repository->expects($this->never())->method($this->anything());
        $this->manager->expects($this->never())->method($this->anything());

        $definition = $this->createWorkflowDefinition(
            [[self::FIELD_NAME => self::ENTITY_ID], ['extraField' => self::ENTITY_ID]],
            [$this->createScope(100), $this->createScope(101)]
        );

        $this->workflowScopeManager->setEnabled(false);
        $this->workflowScopeManager->updateScopes($definition);

        $this->assertEquals(
            [$this->createScope(100), $this->createScope(101)],
            array_values($definition->getScopes()->toArray())
        );
    }

    /**
     * @dataProvider updateScopesExceptionDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testUpdateScopesException(WorkflowDefinition $definition, $exception, $exceptionMessage)
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowScopeManager::SCOPE_TYPE)
            ->willReturn([self::FIELD_NAME => self::ENTITY_CLASS]);

        $this->setExpectedException($exception, $exceptionMessage);

        $this->workflowScopeManager->updateScopes($definition);
    }

    /**
     * @return array
     */
    public function updateScopesExceptionDataProvider()
    {
        return [
            [
                'definition' => $this->createWorkflowDefinition([['test' => self::ENTITY_ID]]),
                'exception' => 'RuntimeException',
                'exceptionMessage' => 'Unknown field name "test" for scope type "workflow_definition".'
            ],
            [
                'definition' => $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]),
                'exception' => 'RuntimeException',
                'exceptionMessage' => 'Could not found entity "stdClass" with id "42".'
            ]
        ];
    }

    /**
     * @param array $scopesConfig
     * @param array $scopes
     * @return WorkflowDefinition
     */
    protected function createWorkflowDefinition(array $scopesConfig = [], array $scopes = [])
    {
        return $this->getEntity(
            WorkflowDefinition::class,
            [
                'configuration' => ['scopes' => $scopesConfig],
                'scopes' => new ArrayCollection($scopes)
            ]
        );
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    protected function createEntity($id)
    {
        $class = self::ENTITY_CLASS;

        $obj = new $class;
        $obj->id = $id;

        return $obj;
    }

    /**
     * @param int $id
     * @return Scope
     */
    protected function createScope($id)
    {
        return $this->getEntity(Scope::class, ['id' => $id]);
    }
}
