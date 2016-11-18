<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionScopeListener;

use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowDefinitionScopeListenerTest extends \PHPUnit_Framework_TestCase
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

    /** @var WorkflowDefinitionScopeListener */
    private $listener;

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

        $this->listener = new WorkflowDefinitionScopeListener($registry, $this->scopeManager);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->repository, $this->manager, $this->scopeManager);
    }

    public function testOnCreateWorkflowDefinitionWithEmptyScopesConfig()
    {
        $this->repository->expects($this->never())->method($this->anything());
        $this->manager->expects($this->never())->method($this->anything());
        $this->scopeManager->expects($this->never())->method($this->anything());

        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->listener->onCreateWorkflowDefinition($event);
    }

    public function testOnCreateWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]));
        $entity = $this->createEntity(self::ENTITY_ID);
        $scope = $this->createScope(42);

        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowDefinitionScopeListener::SCOPE_TYPE)
            ->willReturn(
                [
                    'extraField' => null,
                    self::FIELD_NAME => self::ENTITY_CLASS
                ]
            );
        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(WorkflowDefinitionScopeListener::SCOPE_TYPE, [self::FIELD_NAME => $entity])
            ->willReturn($scope);

        $this->repository->expects($this->once())->method('find')->with(self::ENTITY_ID)->willReturn($entity);
        $this->manager->expects($this->once())->method('flush');

        $this->listener->onCreateWorkflowDefinition($event);

        $this->assertEquals(new ArrayCollection([$scope]), $event->getDefinition()->getScopes());
    }

    /**
     * @dataProvider onCreateExceptionDataProvider
     *
     * @param WorkflowChangesEvent $event
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testOnCreateWorkflowDefinitionException(WorkflowChangesEvent $event, $exception, $exceptionMessage)
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowDefinitionScopeListener::SCOPE_TYPE)
            ->willReturn([self::FIELD_NAME => self::ENTITY_CLASS]);

        $this->setExpectedException($exception, $exceptionMessage);

        $this->listener->onCreateWorkflowDefinition($event);
    }

    /**
     * @return array
     */
    public function onCreateExceptionDataProvider()
    {
        return [
            [
                'event' => $event = new WorkflowChangesEvent(
                    $this->createWorkflowDefinition([['test' => self::ENTITY_ID]])
                ),
                'exception' => 'RuntimeException',
                'exceptionMessage' => 'Unknown field name "test" for scope type "workflow_definition".'
            ],
            [
                'event' => $event = new WorkflowChangesEvent(
                    $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]])
                ),
                'exception' => 'RuntimeException',
                'exceptionMessage' => 'Could not found entity "stdClass" with id "42".'
            ]
        ];
    }

    public function testOnUpdateWorkflowDefinitionWithoutScopesConfigChanges()
    {
        $this->repository->expects($this->never())->method($this->anything());
        $this->manager->expects($this->never())->method($this->anything());
        $this->scopeManager->expects($this->never())->method($this->anything());

        $event = new WorkflowChangesEvent(
            $this->createWorkflowDefinition(
                [
                    [self::FIELD_NAME => self::ENTITY_CLASS]
                ]
            ),
            $this->createWorkflowDefinition(
                [
                    [self::FIELD_NAME => self::ENTITY_CLASS]
                ]
            )
        );

        $this->listener->onUpdateWorkflowDefinition($event);
    }

    public function testOnUpdateWorkflowDefinition()
    {
        $scope1 = $this->createScope(101);
        $scope2 = $this->createScope(102);

        $event = new WorkflowChangesEvent(
            $this->createWorkflowDefinition(
                [[self::FIELD_NAME => self::ENTITY_ID], ['extraField' => self::ENTITY_ID]],
                [$this->createScope(100), $this->createScope(101)]
            ),
            $this->createWorkflowDefinition()
        );
        $entity = $this->createEntity(self::ENTITY_ID);

        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowDefinitionScopeListener::SCOPE_TYPE)
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
                    [WorkflowDefinitionScopeListener::SCOPE_TYPE, [self::FIELD_NAME => $entity], $scope1],
                    [WorkflowDefinitionScopeListener::SCOPE_TYPE, ['extraField' => $entity], $scope2]
                ]
            );

        $this->repository->expects($this->exactly(2))->method('find')->with(self::ENTITY_ID)->willReturn($entity);
        $this->manager->expects($this->once())->method('flush');

        $this->listener->onUpdateWorkflowDefinition($event);

        $this->assertEquals([$scope1, $scope2], array_values($event->getDefinition()->getScopes()->toArray()));
    }

    /**
     * @dataProvider onUpdateExceptionDataProvider
     *
     * @param WorkflowChangesEvent $event
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testOnUpdateWorkflowDefinitionException(WorkflowChangesEvent $event, $exception, $exceptionMessage)
    {
        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(WorkflowDefinitionScopeListener::SCOPE_TYPE)
            ->willReturn([self::FIELD_NAME => self::ENTITY_CLASS]);

        $this->setExpectedException($exception, $exceptionMessage);

        $this->listener->onUpdateWorkflowDefinition($event);
    }

    /**
     * @return array
     */
    public function onUpdateExceptionDataProvider()
    {
        return [
            [
                'event' => $event = new WorkflowChangesEvent(
                    $this->createWorkflowDefinition([['test' => self::ENTITY_ID]]),
                    $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]])
                ),
                'exception' => 'RuntimeException',
                'exceptionMessage' => 'Unknown field name "test" for scope type "workflow_definition".'
            ],
            [
                'event' => $event = new WorkflowChangesEvent(
                    $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]),
                    $this->createWorkflowDefinition([['test' => self::ENTITY_ID]])
                ),
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
                'configuration' => [WorkflowDefinition::CONFIG_SCOPES => $scopesConfig],
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
