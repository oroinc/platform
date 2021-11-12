<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Model\OperationRegistryFilterInterface;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Tests\Unit\Filter\Stub\CallbackOperationRegistryFilter;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class OperationRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $applicationProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ActionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $actionFactory;

    /** @var ConditionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $conditionFactory;

    /** @var AttributeAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeAssembler;

    /** @var FormOptionsAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $formOptionsAssembler;

    /** @var OperationAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->conditionFactory = $this->createMock(ConditionFactory::class);
        $this->attributeAssembler = $this->createMock(AttributeAssembler::class);
        $this->formOptionsAssembler = $this->createMock(FormOptionsAssembler::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->applicationProvider->expects($this->any())
            ->method('isApplicationsValid')
            ->willReturnCallback(function (array $applications) {
                if (count($applications) === 0) {
                    return true;
                }

                return in_array('default', $applications, true);
            });

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });

        $this->assembler = new OperationAssembler(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler
        );
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(
        ?string $entityClass,
        ?string $route,
        ?string $datagrid,
        ?string $group,
        array $expected
    ) {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($this->getConfiguration());

        $registry = $this->getOperationRegistry();

        $this->assertEquals($expected, array_keys($registry->find(
            new OperationFindCriteria($entityClass, $route, $datagrid, $group)
        )));

        // get operations from local cache
        $this->assertEquals($expected, array_keys($registry->find(
            new OperationFindCriteria($entityClass, $route, $datagrid, $group)
        )));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function findDataProvider(): array
    {
        return [
            'empty parameters' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => []
            ],
            'incorrect parameters' => [
                'entityClass' => 'unknown',
                'route' => 'unknown',
                'datagrid' => 'unknown',
                'group' => 'unknown',
                'expected' => []
            ],
            'entity1' => [
                'entityClass' => TestEntity1::class,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => ['operation6', 'operation10', 'operation12', 'operation13', 'operation15', 'operation20']
            ],
            'route1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'expected' => ['operation4', 'operation10']
            ],
            'datagrid1' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => ['operation8', 'operation21']
            ],
            'entity1 group1' => [
                'entityClass' => TestEntity1::class,
                'route' => null,
                'datagrid' => null,
                'group' => 'group1',
                'expected' => ['operation7', 'operation11', 'operation15']
            ],
            'route1 group1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => 'group1',
                'expected' => ['operation5', 'operation11']
            ],
            'datagrid1 group1' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => 'datagrid1',
                'group' => 'group1',
                'expected' => ['operation9']
            ],
            'datagrid exclusion' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => 'datagrid2',
                'group' => null,
                'expected' => ['operation9_0', 'operation20']
            ],
            'route1 & entity1' => [
                'entityClass' => TestEntity1::class,
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'expected' => [
                    'operation4',
                    'operation6',
                    'operation10',
                    'operation12',
                    'operation13',
                    'operation15',
                    'operation20'
                ]
            ],
            'route1 & datagrid1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => ['operation4', 'operation8', 'operation10', 'operation21']
            ],
            'route1 & entity1 & datagrid1' => [
                'entityClass' => TestEntity1::class,
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => [
                    'operation4',
                    'operation6',
                    'operation8',
                    'operation10',
                    'operation12',
                    'operation13',
                    'operation15',
                    'operation21'
                ]
            ],
            'route1 group1 & entity1 group1 & datagrid1 group1' => [
                'entityClass' => TestEntity1::class,
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => 'group1',
                'expected' => ['operation5', 'operation7', 'operation9', 'operation11', 'operation15']
            ],
            'entity2' => [
                'entityClass' => TestEntity2::class,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => ['operation10', 'operation13', 'operation14', 'operation20']
            ],
            'entity3 substitution of operation15 by operation16' => [
                'entityClass' => TestEntity3::class,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => ['operation13', 'operation14', 'operation20']
            ],
            'operation17 matched by group but no substitution and no appearance' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => 'group4',
                'expected' => []
            ],
            'substitute conditional only for specific entity and common group' => [
                'entityClass' => TestEntity3::class,
                'route' => null,
                'datagrid' => null,
                'group' => 'limited',
                'expected' => ['operation18']
            ]
        ];
    }

    /**
     * @dataProvider findByNameDataProvider
     */
    public function testFindByName(
        string $operationName,
        ?string $expected,
        OperationFindCriteria $criteria = null,
        array $filterResult = []
    ) {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'operation1' => $this->createOperationConfig(['label' => 'Label1', 'entities' => ['stdClass']])
                ]
            );

        $filter = $this->createMock(OperationRegistryFilterInterface::class);
        $filter->expects($this->any())
            ->method('filter')
            ->with($this->isType('array'), $criteria)
            ->willReturn($filterResult);

        $registry = $this->getOperationRegistry([$filter]);

        $operation = $registry->findByName($operationName, $criteria);

        $this->assertEquals($expected, $operation ? $operation->getName() : $operation);
    }

    public function findByNameDataProvider(): array
    {
        return [
            'invalid operation name' => [
                'operationName' => 'test',
                'expected' => null
            ],
            'valid operation name' => [
                'operationName' => 'operation1',
                'expected' => 'operation1'
            ],
            'with filter criteria and operation not found' => [
                'operationName' => 'operation1',
                'expected' => null,
                'criteria' => new OperationFindCriteria(null, null, null)
            ],
            'with filter criteria and operation found' => [
                'operationName' => 'operation1',
                'expected' => 'operation1',
                'criteria' => new OperationFindCriteria('stdClass', null, null),
                'filterResult' => ['operation1']
            ],
            'with filter criteria and operation found but not applicable' => [
                'operationName' => 'operation1',
                'expected' => null,
                'criteria' => new OperationFindCriteria('DateTime', null, null),
                'filterResult' => ['operation1']
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getConfiguration(): array
    {
        $configuration = [
            'operation1' => [
                'label' => 'Label1'
            ],
            'operation2' => [
                'label' => 'Label2',
                'enabled' => false,
                'entities' => [
                    TestEntity1::class,
                    TestEntity2::class,
                    TestEntity3::class,
                ],
                'routes' => ['route1', 'route2', 'route3'],
                'datagrids' => ['datagrid1', 'datagrid2', 'datagrid3']
            ],
            'operation3' => [
                'label' => 'Label3',
                'applications' => ['commerce'],
                'groups' => ['group1'],
                'entities' => [
                    TestEntity1::class,
                    TestEntity2::class,
                    TestEntity3::class,
                ],
                'routes' => ['route1', 'route2', 'route3'],
                'datagrids' => ['datagrid1', 'datagrid2', 'datagrid3']
            ],
            'operation4' => [
                'label' => 'Label4',
                'routes' => ['route1']
            ],
            'operation5' => [
                'label' => 'Label5',
                'groups' => ['group1'],
                'routes' => ['route1']
            ],
            'operation6' => [
                'label' => 'Label6',
                'entities' => [TestEntity1::class]
            ],
            'operation7' => [
                'label' => 'Label7',
                'groups' => ['group1'],
                'entities' => [TestEntity1::class]
            ],
            'operation8' => [
                'label' => 'Label8',
                'datagrids' => ['datagrid1'],
            ],
            'operation9' => [
                'label' => 'Label9',
                'groups' => ['group1'],
                'datagrids' => ['datagrid1'],
            ],
            'operation9_0' => [
                'label' => 'Label datagrids 1',
                'for_all_datagrids' => true,
                'exclude_datagrids' => ['datagrid1']
            ],
            'operation10' => [
                'label' => 'Label10',
                'entities' => [
                    TestEntity1::class,
                    TestEntity2::class,
                ],
                'routes' => ['route1', 'route2']
            ],
            'operation11' => [
                'label' => 'Label11',
                'groups' => ['group1'],
                'entities' => [
                    TestEntity1::class,
                    TestEntity2::class,
                ],
                'routes' => ['route1', 'route2']
            ],
            'operation12' => [
                'label' => 'Label12',
                'applications' => ['default'],
                'entities' => [TestEntity1::class],
            ],
            'operation13' => [
                'label' => 'Label13',
                'for_all_entities' => true
            ],
            'operation14' => [
                'label' => 'Label14',
                'for_all_entities' => true,
                'entities' => [TestEntity1::class],
                'exclude_entities' => [TestEntity1::class],
            ],
            'operation15' => [
                'label' => 'Label15',
                'entities' => [TestEntity1::class],
                'groups' => ['', 'group1']
            ],
            'operation16' => [
                'label' => 'Label17Substituted15',
                'substitute_operation' => 'operation15'
            ],
            'operation17' => [
                'label' => 'Label17',
                'substitute_operation' => 'unreachableAction',
                'groups' => ['group5']
            ],
            'operation18' => [
                'label' => 'Label18',
                'for_all_entities' => true,
                'groups' => ['limited']
            ],
            'operation19' => [
                'label' => 'Label18 Specific Entity Replacement',
                'substitute_operation' => 'operation18',
                'entities' => [TestEntity3::class],
                'groups' => ['limited']
            ],
            'operation20' => [
                'label' => 'Label20',
                'for_all_entities' => true,
                'for_all_datagrids' => true,
                'exclude_datagrids' => ['datagrid1']
            ],
            'operation21' => [
                'label' => 'Datagrid and exclude_entities matches',
                'datagrids' => ['datagrid1'],
                'exclude_entities' => [TestEntity1::class],
            ],
        ];

        return array_map([$this, 'createOperationConfig'], $configuration);
    }

    private function createOperationConfig(array $config): array
    {
        return array_merge(
            [
                'label' => uniqid('operation_', false),
                'enabled' => true,
                'applications' => [],
                'groups' => [],
                'entities' => [],
                'exclude_entities' => [],
                'for_all_entities' => false,
                'routes' => [],
                'datagrids' => [],
                'exclude_datagrids' => [],
                'for_all_datagrids' => false
            ],
            $config
        );
    }

    public function testOuterFilterArguments()
    {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'operation1' => $this->createOperationConfig(
                        [
                            'datagrids' => 'd1',
                            'label' => 'Operation1'
                        ]
                    )
                ]
            );

        $filter = $this->createMock(OperationRegistryFilterInterface::class);

        $criteria3 = new OperationFindCriteria('e1', 'r1', 'd1');
        $filter->expects($this->once())
            ->method('filter')
            ->with(
                $this->callback(function (array $operations) {
                    return $operations['operation1'] instanceof Operation && count($operations) === 1;
                }),
                $criteria3
            );

        $registry = $this->getOperationRegistry([$filter]);
        $registry->find($criteria3);
    }

    public function testOuterFiltering()
    {
        $this->configurationProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'operation1' => $this->createOperationConfig([
                        'for_all_entities' => true,
                        'datagrids' => ['d1', 'd2']
                    ]),
                    'operation2' => $this->createOperationConfig([
                        'for_all_entities' => true,
                        'datagrids' => ['d1', 'd2']
                    ]),
                    'operation3' => $this->createOperationConfig([
                        'for_all_entities' => true,
                        'datagrids' => ['d1', 'd2']
                    ])
                ]
            );

        $registry = $this->getOperationRegistry();
        $this->assertEquals(
            ['operation1', 'operation2', 'operation3'],
            array_keys($registry->find(new OperationFindCriteria('e1', 'r1', 'd1')))
        );

        $filter1 = $this->createFilter(
            function (Operation $operation, OperationFindCriteria $criteria) {
                if ($criteria->getEntityClass() === 'e1') {
                    return $operation->getName() !== 'operation1';
                }

                return true;
            }
        );
        $filter2 = $this->createFilter(
            function (Operation $operation, OperationFindCriteria $criteria) {
                if ($criteria->getEntityClass() === 'e2') {
                    return $operation->getName() !== 'operation2';
                }

                return true;
            }
        );
        $filter3 = $this->createFilter(
            function (Operation $operation, OperationFindCriteria $criteria) {
                if ($criteria->getEntityClass() === 'e3') {
                    return $operation->getName() !== 'operation3';
                }

                return true;
            }
        );

        $registry = $this->getOperationRegistry([$filter1, $filter2, $filter3]);

        $this->assertEquals(
            ['operation2', 'operation3'],
            array_keys($registry->find(new OperationFindCriteria('e1', null, null))),
            'first filter should be applied'
        );

        $this->assertEquals(
            ['operation1', 'operation3'],
            array_keys($registry->find(new OperationFindCriteria('e2', null, null))),
            'second filter should be applied'
        );

        $this->assertEquals(
            ['operation1', 'operation2'],
            array_keys($registry->find(new OperationFindCriteria('e3', null, null))),
            'third filter should be applied'
        );
    }

    private function getOperationRegistry(array $filters = []): OperationRegistry
    {
        return new OperationRegistry(
            $filters,
            $this->configurationProvider,
            $this->assembler,
            $this->applicationProvider,
            $this->doctrineHelper
        );
    }

    private function createFilter(callable $callable): CallbackOperationRegistryFilter
    {
        return new CallbackOperationRegistryFilter(
            function (array $operations, OperationFindCriteria $criteria) use ($callable) {
                return array_filter(
                    $operations,
                    function (Operation $operation) use ($criteria, $callable) {
                        return $callable($operation, $criteria);
                    }
                );
            }
        );
    }
}
