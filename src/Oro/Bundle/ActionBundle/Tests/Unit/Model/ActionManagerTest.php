<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ActionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var ActionManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ActionManager($this->operationRegistry, $this->doctrineHelper, $this->contextHelper);
    }

    /**
     * @dataProvider hasActionsDataProvider
     *
     * @param array $actions
     * @param bool $expected
     */
    public function testHasActions(array $actions, $expected)
    {
        $this->markTestIncomplete();

        $this->operationRegistry->expects($this->once())->method('find')->willReturn($actions);

        $this->assertContextHelperCalled();
        $this->assertEquals($expected, $this->manager->hasActions());
    }

    /**
     * @return array
     */
    public function hasActionsDataProvider()
    {
        return [
            'no actions' => [
                'actions' => [],
                'expected' => false
            ],
            'route1 & entity1' => [
                'actions' => [
                    $this->getActions('action2'),
                    $this->getActions('action1'),
                    $this->getActions('action3')
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getActionsProvider
     *
     * @param string $class
     * @param string $route
     * @param string $datagrid
     * @param string $group
     * @param array $context
     * @param array $actions
     * @param array $expected
     */
    public function testGetActions($class, $route, $datagrid, $group, array $context, array $actions, array $expected)
    {
        $this->assertContextHelperCalled($context);

        $this->operationRegistry->expects($this->once())
            ->method('find')
            ->with($class, $route, $datagrid, $group)
            ->willReturn($actions);

        $this->assertGetActions($expected, $context);
    }

    /**
     * @return array
     */
    public function getActionsProvider()
    {
        return [
            'empty context' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => [],
                'actions' => [],
                'expectedActions' => []
            ],
            'incorrect context parameter' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => ['entityId' => 1],
                'actions' => [],
                'expectedActions' => []
            ],
            'route1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'context' => ['route' => 'route1'],
                'actions' => [
                    'action2' => $this->getActions('action2'),
                    'action4' => $this->getActions('action4'),
                ],
                'expectedActions' => [
                    'action4',
                    'action2',
                ]
            ],
            'entity1 without id' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => ['entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                'actions' => [],
                'expectedActions' => []
            ],
            'entity1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => [
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'actions' => [
                    'action3' => $this->getActions('action3'),
                    'action4' => $this->getActions('action4'),
                    'action6' => $this->getActions('action6'),
                ],
                'expectedActions' => [
                    'action4',
                    'action3',
                    'action6'
                ]
            ],
            'route1 & entity1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'actions' => [
                    'action2' => $this->getActions('action2'),
                    'action3' => $this->getActions('action3'),
                    'action4' => $this->getActions('action4'),
                    'action6' => $this->getActions('action6'),
                ],
                'expectedActions' => [
                        'action4',
                        'action3',
                        'action2',
                        'action6'
                ]
            ],
            'full context' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => 'route1',
                'datagrid' => 'grid1',
                'group' => 'group1',
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                    'datagrid' => 'grid1',
                    'group' => 'group1'
                ],
                'actions' => [
                    'action2' => $this->getActions('action2'),
                    'action3' => $this->getActions('action3'),
                    'action4' => $this->getActions('action4'),
                    'action6' => $this->getActions('action6')
                ],
                'expectedActions' => ['action4', 'action3', 'action2', 'action6']
            ]
        ];
    }

    /**
     * @dataProvider getActionDataProvider
     *
     * @param string $operationName
     * @param Operation|null $operation
     * @param bool $isAvailable
     */
    public function testGetAction($operationName, $operation, $isAvailable)
    {
        if (!$operation || !$isAvailable) {
            $this->setExpectedException(
                '\Oro\Bundle\ActionBundle\Exception\ActionNotFoundException',
                sprintf('Action with name "%s" not found', $operationName)
            );
        }

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with($operationName)
            ->willReturn($operation);

        $this->assertSame($operation, $this->manager->getAction($operationName, new ActionData()));
    }

    /**
     * @return array
     */
    public function getActionDataProvider()
    {
        return [
            'no action' => [
                'actionName' => 'test',
                'action' => null,
                'isAvailable' => true
            ],
            'action not available' => [
                'actionName' => 'test',
                'action' => $this->createActionMock(false),
                'isAvailable' => false
            ],
            'valid action' => [
                'actionName' => 'test_action',
                'action' => $this->createActionMock(true),
                'isAvailable' => true
            ]
        ];
    }

    /**
     * @dataProvider executeByContextDataProvider
     *
     * @param array $context
     * @param ActionData $actionData
     */
    public function testExecuteByContext(array $context, ActionData $actionData)
    {
        if ($actionData->getEntity()) {
            $this->assertEntityManagerCalled('stdClass');
        }

        $errors = new ArrayCollection();

        $action = $this->createActionMock();
        $action->expects($this->once())
            ->method('execute')
            ->willReturn(function ($param1, $param2) use ($actionData, $errors) {
                $this->assertSame($actionData, $param1);
                $this->assertSame($errors, $param2);

                return true;
            });

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('test_action')
            ->willReturn($action);

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->with($context)
            ->willReturn($actionData);

        $this->manager->executeByContext('test_action', $context, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @return array
     */
    public function executeByContextDataProvider()
    {
        return [
            'without entity' => [
                'context' => [],
                'actionData' => new ActionData(),
            ],
            'route1' => [
                'context' => [
                    'route' => 'route1'
                ],
                'actionData' => new ActionData(),
            ],
            'route1 without entity id' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass'
                ],
                'actionData' => new ActionData(),
            ],
            'entity' => [
                'context' => [
                    'entityClass' => 'stdClass',
                    'entityId' => 1,
                    'datagrid' => 'grid1',
                    'group' => 'group1'
                ],
                'actionData' => new ActionData(),
            ],
            'route1 and entity' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'actionData' => new ActionData(),
            ],
            'route1 and entity with action data and entity' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'actionData' => new ActionData(['data' => new \stdClass]),
            ],
        ];
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param ActionData $actionData
     * @param bool $exception
     */
    public function testExecute(ActionData $actionData, $exception = false)
    {
        if ($actionData->getEntity()) {
            $this->assertEntityManagerCalled(get_class($actionData->getEntity()), $exception);

            if ($exception) {
                $this->setExpectedException('\Exception', 'Flush exception');
            }
        }

        $errors = new ArrayCollection();

        $action = $this->createActionMock();
        $action->expects($this->once())
            ->method('execute')
            ->willReturn(function ($param1, $param2) use ($actionData, $errors) {
                $this->assertSame($actionData, $param1);
                $this->assertSame($errors, $param2);

                return true;
            });

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('test_action')
            ->willReturn($action);

        $this->manager->execute('test_action', $actionData, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'empty data' => [
                'actionData' => new ActionData()
            ],
            'with entity' => [
                'actionData' => new ActionData(['data' => new \stdClass])
            ],
            'exception' => [
                'actionData' => new ActionData(['data' => new \stdClass]),
                'exception' => true
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\ActionNotFoundException
     * @expectedExceptionMessage Action with name "test_operation" not found
     */
    public function testExecuteByContextException()
    {
        $this->assertContextHelperCalled([], 0, 1);

        $this->manager->executeByContext('test_operation');
    }

    /**
     * @dataProvider getFrontendTemplateDataProvider
     *
     * @param string $operationName
     * @param string $expected
     */
    public function testGetFrontendTemplate($operationName, $expected)
    {
        $this->assertContextHelperCalled(
            [
                'route' => 'route1',
                'entityClass' => 'stdClass',
                'entityId' => 1,
                'datagrid' => 'datagrid_name',
            ],
            0,
            1
        );

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with($operationName)
            ->willReturn($this->getActions($operationName));

        $this->assertEquals($expected, $this->manager->getFrontendTemplate($operationName));
    }

    /**
     * @return array
     */
    public function getFrontendTemplateDataProvider()
    {
        return [
            [
                'actionName' => 'action2',
                'expected' => ActionManager::DEFAULT_FORM_TEMPLATE
            ],
            [
                'actionName' => 'action1',
                'expected' => ActionManager::DEFAULT_PAGE_TEMPLATE
            ],
            [
                'actionName' => 'action4',
                'expected' => 'test.html.twig'
            ]
        ];
    }

    /**
     * @param string $className
     * @param bool $throwException
     */
    protected function assertEntityManagerCalled($className, $throwException = false)
    {
        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->once())->method('beginTransaction');

        if ($throwException) {
            $entityManager->expects($this->once())
                ->method('flush')
                ->willThrowException(new \Exception('Flush exception'));
            $entityManager->expects($this->once())->method('rollback');
        } else {
            $entityManager->expects($this->once())->method('flush');
            $entityManager->expects($this->once())->method('commit');
        }

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->isInstanceOf($className))
            ->willReturn($entityManager);
    }

    /**
     * @param array $expectedOperations
     * @param array $inputContext
     */
    protected function assertGetActions(array $expectedOperations, array $inputContext)
    {
        $this->markTestIncomplete();

        $this->contextHelper->expects($this->any())
            ->method('getContext')
            ->willReturnCallback(function ($context) {
                return array_merge(
                    [
                        'route' => null,
                        'entityId' => null,
                        'entityClass' => null,
                        'datagrid' => null,
                        'group' => null
                    ],
                    $context
                );
            });

        $this->contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $this->assertEquals($expectedOperations, array_keys($this->manager->getActions($inputContext)));
    }

    /**
     * @param array $context
     * @param int $getContextCalls
     * @param int $getActionDataCalls
     */
    protected function assertContextHelperCalled(array $context = [], $getContextCalls = 1, $getActionDataCalls = 1)
    {
        $this->contextHelper->expects($this->exactly($getContextCalls))
            ->method('getContext')
            ->willReturn(
                array_merge(
                    [
                        'route' => null,
                        'entityId' => null,
                        'entityClass' => null,
                        'datagrid' => null,
                        'group' => null
                    ],
                    $context
                )
            );

        $this->contextHelper->expects($this->exactly($getActionDataCalls))
            ->method('getActionData')
            ->willReturn(new ActionData());
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getActions($name = null)
    {
        $actions = [
            'action1' => $this->getAction('action1', 50, ['show_dialog' => false]),
            'action2' => $this->getAction('action2', 40, ['show_dialog' => true], [], ['route1']),
            'action3' => $this->getAction(
                'action3',
                30,
                ['show_dialog' => true],
                ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ),
            'action4' => $this->getAction(
                'action4',
                20,
                ['template' => 'test.html.twig', 'show_dialog' => true],
                [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2'
                ],
                ['route1', 'route2']
            ),
            'action5' => $this->getAction(
                'action5',
                10,
                ['show_dialog' => true],
                [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3'
                ],
                ['route2', 'route3'],
                [],
                [],
                false
            ),
            'action6' => $this->getAction(
                'action6',
                50,
                ['show_dialog' => true],
                ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                ['route2', 'route3']
            ),
            'action7' => $this->getAction(
                'action7',
                50,
                ['show_dialog' => true],
                [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3'
                ],
                ['route1', 'route2', 'route3']
            )
        ];

        return $name ? $actions[$name] : $actions;
    }

    /**
     * @param string $name
     * @param int $order
     * @param array $frontendOptions
     * @param array $entities
     * @param array $routes
     * @param array $datagrids
     * @param array $group
     * @param bool $enabled
     * @return Operation
     */
    protected function getAction(
        $name,
        $order = 10,
        array $frontendOptions = [],
        array $entities = [],
        array $routes = [],
        array $datagrids = [],
        array $group = [],
        $enabled = true
    ) {
        $definition = new OperationDefinition();
        $definition
            ->setName($name)
            ->setLabel('Label ' . $name)
            ->setEnabled($enabled)
            ->setOrder($order)
            ->setRoutes($routes)
            ->setEntities($entities)
            ->setDatagrids($datagrids)
            ->setGroups($group)
            ->setFrontendOptions($frontendOptions);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
        $functionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
        $conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
        $attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
        $formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        return new Operation(
            $functionFactory,
            $conditionFactory,
            $attributeAssembler,
            $formOptionsAssembler,
            new OperationActionGroupAssembler(),
            $definition
        );
    }

    /**
     * @param bool $isAvailable
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionMock($isAvailable = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->once())->method('isAvailable')->willReturn($isAvailable);

        return $operation;
    }
}
