<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OperationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $operationRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var OperationManager */
    protected $manager;

    /** @var Collection|\PHPUnit_Framework_MockObject_MockObject */
    private $errorsCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operationRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->errorsCollection = new ArrayCollection();

        $this->manager = new OperationManager(
            $this->operationRegistry,
            $this->actionGroupRegistry,
            $this->contextHelper
        );
    }

    /**
     * @dataProvider hasOperationsDataProvider
     *
     * @param array $operations
     * @param bool $expected
     */
    public function testHasOperations(array $operations, $expected)
    {
        $this->operationRegistry->expects($this->once())->method('find')->willReturn($operations);

        $this->assertContextHelperCalled();
        $this->assertEquals($expected, $this->manager->hasOperations());
    }

    /**
     * @return array
     */
    public function hasOperationsDataProvider()
    {
        return [
            'no operations' => [
                'operations' => [],
                'expected' => false
            ],
            'route1 & entity1' => [
                'operations' => [
                    $this->getOperations('operation2'),
                    $this->getOperations('operation1'),
                    $this->getOperations('operation3')
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getOperationsProvider
     *
     * @param string $class
     * @param string $route
     * @param string $datagrid
     * @param string $group
     * @param array $context
     * @param array $operations
     * @param array $expected
     */
    public function testgetOperations(
        $class,
        $route,
        $datagrid,
        $group,
        array $context,
        array $operations,
        array $expected
    ) {
        $this->assertContextHelperCalled($context);

        $this->operationRegistry->expects($this->once())
            ->method('find')
            ->with($class, $route, $datagrid, $group)
            ->willReturn($operations);

        $this->assertGetOperations($expected, $context);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getOperationsProvider()
    {
        return [
            'empty context' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => [],
                'operations' => [],
                'expectedOperations' => []
            ],
            'incorrect context parameter' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => ['entityId' => 1],
                'operations' => [],
                'expectedOperations' => []
            ],
            'route1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'context' => ['route' => 'route1'],
                'operations' => [
                    'operation2' => $this->getOperations('operation2'),
                    'operation4' => $this->getOperations('operation4'),
                ],
                'expectedOperations' => [
                    'operation4',
                    'operation2',
                ]
            ],
            'entity1 without id' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'context' => ['entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                'operations' => [],
                'expectedOperations' => []
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
                'operations' => [
                    'operation3' => $this->getOperations('operation3'),
                    'operation4' => $this->getOperations('operation4'),
                    'operation6' => $this->getOperations('operation6'),
                ],
                'expectedOperations' => [
                    'operation4',
                    'operation3',
                    'operation6'
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
                'operations' => [
                    'operation2' => $this->getOperations('operation2'),
                    'operation3' => $this->getOperations('operation3'),
                    'operation4' => $this->getOperations('operation4'),
                    'operation6' => $this->getOperations('operation6'),
                ],
                'expectedOperations' => [
                    'operation4',
                    'operation3',
                    'operation2',
                    'operation6'
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
                'operations' => [
                    'operation2' => $this->getOperations('operation2'),
                    'operation3' => $this->getOperations('operation3'),
                    'operation4' => $this->getOperations('operation4'),
                    'operation6' => $this->getOperations('operation6')
                ],
                'expectedOperations' => ['operation4', 'operation3', 'operation2', 'operation6']
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
    public function testGetOperation($operationName, $operation, $isAvailable)
    {
        if (!$operation || !$isAvailable) {
            $this->setExpectedException(
                '\Oro\Bundle\ActionBundle\Exception\OperationNotFoundException',
                sprintf('Operation with name "%s" not found', $operationName)
            );
        }

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with($operationName)
            ->willReturn($operation);

        $this->assertSame($operation, $this->manager->getOperation($operationName, new ActionData()));
    }

    /**
     * @return array
     */
    public function getActionDataProvider()
    {
        return [
            'no operation' => [
                'operationName' => 'test',
                'operation' => null,
                'isAvailable' => true
            ],
            'operation not available' => [
                'operationName' => 'test',
                'operation' => $this->createOperationMock(false),
                'isAvailable' => false
            ],
            'valid operation' => [
                'operationName' => 'test_operation',
                'operation' => $this->createOperationMock(true),
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
        $errors = new ArrayCollection();

        $operation = $this->createOperationMock($actionData);

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('test_operation')
            ->willReturn($operation);


        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->with($context)
            ->willReturn($actionData);

        $this->manager->executeByContext('test_operation', $context, $errors);

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
            'route1 and entity with operation data and entity' => [
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
     */
    public function testExecute(ActionData $actionData)
    {
        $operation = $this->createOperationMock($actionData);
        $operation->expects($this->any())
            ->method('execute')
            ->willReturn(function ($param1, $param2) use ($actionData) {
                $this->assertSame($actionData, $param1);
                $this->assertSame($this->errorsCollection, $param2);
            });

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('test_operation')
            ->willReturn($operation);

        $this->manager->execute('test_operation', $actionData, $this->errorsCollection);

        $this->assertEmpty($this->errorsCollection->toArray());
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
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\OperationNotFoundException
     * @expectedExceptionMessage Operation with name "test_operation" not found
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
            ->willReturn($this->getOperations($operationName));

        $this->assertEquals($expected, $this->manager->getFrontendTemplate($operationName));
    }

    /**
     * @return array
     */
    public function getFrontendTemplateDataProvider()
    {
        return [
            [
                'operationName' => 'operation2',
                'expected' => OperationManager::DEFAULT_FORM_TEMPLATE
            ],
            [
                'operationName' => 'operation1',
                'expected' => OperationManager::DEFAULT_PAGE_TEMPLATE
            ],
            [
                'operationName' => 'operation4',
                'expected' => 'test.html.twig'
            ]
        ];
    }

    /**
     * @param array $expectedOperations
     * @param array $inputContext
     */
    protected function assertGetOperations(array $expectedOperations, array $inputContext)
    {
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

        $this->assertEquals($expectedOperations, array_keys($this->manager->getOperations($inputContext)));
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
    protected function getOperations($name = null)
    {
        $operations = [
            'operation1' => $this->getOperation('operation1', 50, ['show_dialog' => false]),
            'operation2' => $this->getOperation('operation2', 40, ['show_dialog' => true], [], ['route1']),
            'operation3' => $this->getOperation(
                'operation3',
                30,
                ['show_dialog' => true],
                ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ),
            'operation4' => $this->getOperation(
                'operation4',
                20,
                ['template' => 'test.html.twig', 'show_dialog' => true],
                [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2'
                ],
                ['route1', 'route2']
            ),
            'operation5' => $this->getOperation(
                'operation5',
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
            'operation6' => $this->getOperation(
                'operation6',
                50,
                ['show_dialog' => true],
                ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                ['route2', 'route3']
            ),
            'operation7' => $this->getOperation(
                'operation7',
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

        return $name ? $operations[$name] : $operations;
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
    protected function getOperation(
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

        /* @var $actionFactory \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
        $actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $conditionFactory \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
        $conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $attributeAssembler \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
        $attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $formOptionsAssembler \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
        $formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        return new Operation(
            $actionFactory,
            $conditionFactory,
            $attributeAssembler,
            $formOptionsAssembler,
            $definition
        );
    }

    /**
     * @param bool $isAvailable
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOperationMock($isAvailable = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $operation->expects($this->once())->method('isAvailable')->willReturn($isAvailable);

        return $operation;
    }

    /**
     * @param bool $isAllowed
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionGroupMock($isAllowed = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())->method('isAllowed')->willReturn($isAllowed);

        return $operation;
    }
}
