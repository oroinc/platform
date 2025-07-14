<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DatagridConfigurationTest extends TestCase
{
    private DatagridConfiguration $configuration;

    #[\Override]
    protected function setUp(): void
    {
        $this->configuration = DatagridConfiguration::create([]);
    }

    public function testGetOrmQueryForUndefinedDatasourceType(): void
    {
        self::assertInstanceOf(OrmQueryConfiguration::class, $this->configuration->getOrmQuery());
    }

    public function testGetOrmQueryForOrmDatasourceType(): void
    {
        $this->configuration->setDatasourceType(OrmDatasource::TYPE);
        self::assertInstanceOf(OrmQueryConfiguration::class, $this->configuration->getOrmQuery());
    }

    public function testGetOrmQueryForNotOrmDatasourceType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The expected data grid source type is "orm". Actual source type is "another".');

        $this->configuration->setDatasourceType('another');
        $this->configuration->getOrmQuery();
    }

    public function testIsOrmDatasource(): void
    {
        // the datasource type is not set
        self::assertFalse($this->configuration->isOrmDatasource());
        // ORM datasource
        $this->configuration->setDatasourceType(OrmDatasource::TYPE);
        self::assertTrue($this->configuration->isOrmDatasource());
        // not ORM datasource
        $this->configuration->setDatasourceType('another');
        self::assertFalse($this->configuration->isOrmDatasource());
    }

    public function testDatasourceType(): void
    {
        // test initial value
        self::assertNull($this->configuration->getDatasourceType());
        // test setter
        self::assertSame($this->configuration, $this->configuration->setDatasourceType('test'));
        // test previously set value
        self::assertEquals('test', $this->configuration->getDatasourceType());
    }

    public function testExtendedEntityClassName(): void
    {
        // test initial value
        self::assertNull($this->configuration->getExtendedEntityClassName());
        // test setter
        self::assertSame($this->configuration, $this->configuration->setExtendedEntityClassName('test'));
        // test previously set value
        self::assertEquals('test', $this->configuration->getExtendedEntityClassName());
        // test remove value
        self::assertSame($this->configuration, $this->configuration->setExtendedEntityClassName(null));
        self::assertNull($this->configuration->getExtendedEntityClassName());
    }

    /**
     * @dataProvider getAclResourceDataProvider
     */
    public function testGetAclResource(array $params, bool $expected): void
    {
        $this->configuration->merge($params);
        $this->assertEquals($expected, $this->configuration->getAclResource());
    }

    public static function getAclResourceDataProvider(): array
    {
        return [
            [
                'params' => [],
                'expected' => false,
            ],
            [
                'params' => ['acl_resource' => false],
                'expected' => false,
            ],
            [
                'params' => ['acl_resource' => true],
                'expected' => true,
            ],
            [
                'params' => ['acl_resource' => true],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider isDatasourceSkipAclApplyDataProvider
     */
    public function testIsDatasourceSkipAclApply(array $params, bool $expected): void
    {
        $this->configuration->merge($params);
        $this->assertEquals($expected, $this->configuration->isDatasourceSkipAclApply());
    }

    public static function isDatasourceSkipAclApplyDataProvider(): array
    {
        return [
            [
                'params' => ['source' => []],
                'expected' => false,
            ],
            [
                'params' => ['source' => ['skip_acl_apply' => false]],
                'expected' => false,
            ],
            [
                'params' => ['source' => ['skip_acl_apply' => true]],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider addColumnDataProvider
     */
    public function testAddColumn(
        array $expected,
        string $name,
        array $definition,
        ?string $select = null,
        array $sorter = [],
        array $filter = []
    ): void {
        $this->configuration->addColumn(
            $name,
            $definition,
            $select,
            $sorter,
            $filter
        );

        $configArray = $this->configuration->toArray();
        $this->assertEquals($expected, $configArray);
    }

    public function testAddColumnWithoutName(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('DatagridConfiguration::addColumn: name should not be empty');

        $this->configuration->addColumn(null, []);
    }

    public function testUpdateLabelWithoutName(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('DatagridConfiguration::updateLabel: name should not be empty');

        $this->configuration->updateLabel(null, 'label1');
    }

    public function testUpdateLabel(): void
    {
        $this->configuration->updateLabel('testColumn', 'label1');

        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            ['columns' => ['testColumn' => ['label' => 'label1']]],
            $configArray
        );

        $this->configuration->updateLabel('testColumn1', null);
        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            [
                'columns' => [
                    'testColumn'  => ['label' => 'label1'],
                    'testColumn1' => ['label' => null],
                ]
            ],
            $configArray
        );

        $this->configuration->updateLabel('testColumn', 'label2');
        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            [
                'columns' => [
                    'testColumn'  => ['label' => 'label2'],
                    'testColumn1' => ['label' => null],
                ]
            ],
            $configArray
        );
    }

    public function testAddSelect(): void
    {
        $this->configuration->getOrmQuery()->addSelect('testColumn');

        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            [
                'source' => [
                    'query' => ['select' => ['testColumn']],
                ]
            ],
            $configArray
        );
    }

    public function testJoinTable(): void
    {
        $this->configuration->getOrmQuery()->addLeftJoin('rootAlias.association', 'joinAlias');

        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            [
                'source' => [
                    'query' => ['join' => ['left' => [['join' => 'rootAlias.association', 'alias' => 'joinAlias']]]],
                ]
            ],
            $configArray
        );
    }

    public function testRemoveColumn(): void
    {
        $this->configuration->addColumn('testColumn', ['param' => 123], null, ['param' => 123], ['param' => 123]);

        $configArray = $this->configuration->toArray();
        $this->assertTrue(isset($configArray['columns']['testColumn']));

        $this->configuration->removeColumn('testColumn');
        $configArray = $this->configuration->toArray();

        $this->assertEmpty($configArray['columns']);
        $this->assertEmpty($configArray['sorters']['columns']);
        $this->assertEmpty($configArray['filters']['columns']);
    }

    public function testIsDatagridExtendedFrom(): void
    {
        self::assertFalse($this->configuration->isDatagridExtendedFrom('some-datagrid-name'));

        $this->configuration->offsetSet(SystemAwareResolver::KEY_EXTENDED_FROM, null);
        self::assertFalse($this->configuration->isDatagridExtendedFrom('some-datagrid-name'));

        $this->configuration->offsetSet(SystemAwareResolver::KEY_EXTENDED_FROM, ['some-other-datagrid-name']);
        self::assertFalse($this->configuration->isDatagridExtendedFrom('some-datagrid-name'));

        $this->configuration->offsetSet(SystemAwareResolver::KEY_EXTENDED_FROM, [
            'some-datagrid-name',
            'some-other-datagrid-name'
        ]);
        self::assertTrue($this->configuration->isDatagridExtendedFrom('some-datagrid-name'));
    }

    public function testAddProperty(): void
    {
        $this->configuration->addProperty('name', ['definition']);
        $configArray = $this->configuration->toArray();

        $this->assertEquals(
            [
                'properties' => [
                    'name' => [
                        0 => 'definition'
                    ]
                ]
            ],
            $configArray
        );
    }

    public static function addColumnDataProvider(): array
    {
        return [
            'all data supplied'         => [
                'expected'   => [
                    'source'  => [
                        'query' => ['select' => ['entity.testColumn1',]],
                    ],
                    'columns' => ['testColumn1' => ['testParam1' => 'abc', 'testParam2' => 123,]],
                    'sorters' => ['columns' => ['testColumn1' => ['data_name' => 'testColumn1']]],
                    'filters' => ['columns' => ['testColumn1' => ['data_name' => 'testColumn1', 'type' => 'string']]],
                ],
                'name'       => 'testColumn1',
                'definition' => ['testParam1' => 'abc', 'testParam2' => 123,],
                'select'     => 'entity.testColumn1',
                'sorter'     => ['data_name' => 'testColumn1'],
                'filter'     => ['data_name' => 'testColumn1', 'type' => 'string'],
            ],
            'without sorter and filter' => [
                'expected'   => [
                    'source'  => [
                        'query' => ['select' => ['entity.testColumn2',]],
                    ],
                    'columns' => ['testColumn2' => ['testParam1' => 'abc', 'testParam2' => 123,]],
                ],
                'name'       => 'testColumn2',
                'definition' => ['testParam1' => 'abc', 'testParam2' => 123,],
                'select'     => 'entity.testColumn2',
            ],
            'without select part'       => [
                'expected'   => [
                    'columns' => ['testColumn2' => ['testParam1' => 'abc', 'testParam2' => 123,]],
                ],
                'name'       => 'testColumn2',
                'definition' => ['testParam1' => 'abc', 'testParam2' => 123,],
            ],
            'without sorter and select' => [
                'expected'   => [
                    'columns' => ['testColumn1' => ['testParam1' => 'abc', 'testParam2' => 123,]],
                    'filters' => ['columns' => ['testColumn1' => ['data_name' => 'testColumn1', 'type' => 'string']]],
                ],
                'name'       => 'testColumn1',
                'definition' => ['testParam1' => 'abc', 'testParam2' => 123,],
                'select'     => null,
                'sorter'     => [],
                'filter'     => ['data_name' => 'testColumn1', 'type' => 'string'],
            ],
            'with empty definition'     => [
                'expected'   => [
                    'columns' => ['testColumn1' => []],
                ],
                'name'       => 'testColumn1',
                'definition' => [],
            ],
        ];
    }

    /**
     * @dataProvider moveColumnBeforeDataProvider
     */
    public function testMoveColumnBefore(array $columns, string $name, string $targetName, array $expectedColumns): void
    {
        $this->configuration->offsetSetByPath('[columns]', $columns);
        $this->configuration->moveColumnBefore($name, $targetName);
        self::assertSame($expectedColumns, $this->configuration->offsetGetByPath('[columns]'));
    }

    public static function moveColumnBeforeDataProvider(): array
    {
        return [
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column3',
                'column2',
                ['column1' => ['param' => '1'], 'column3' => ['param' => '3'], 'column2' => ['param' => '2']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column3',
                'column1',
                ['column3' => ['param' => '3'], 'column1' => ['param' => '1'], 'column2' => ['param' => '2']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column5',
                'column2',
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column3',
                'column5',
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']]
            ],
            [
                ['column1' => null, 'column2' => null, 'column3' => null],
                'column3',
                'column2',
                ['column1' => null, 'column3' => null, 'column2' => null]
            ]
        ];
    }

    /**
     * @dataProvider moveColumnAfterDataProvider
     */
    public function testMoveColumnAfter(array $columns, string $name, string $targetName, array $expectedColumns): void
    {
        $this->configuration->offsetSetByPath('[columns]', $columns);
        $this->configuration->moveColumnAfter($name, $targetName);
        self::assertSame($expectedColumns, $this->configuration->offsetGetByPath('[columns]'));
    }

    public static function moveColumnAfterDataProvider(): array
    {
        return [
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column1',
                'column2',
                ['column2' => ['param' => '2'], 'column1' => ['param' => '1'], 'column3' => ['param' => '3']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column1',
                'column3',
                ['column2' => ['param' => '2'], 'column3' => ['param' => '3'], 'column1' => ['param' => '1']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column5',
                'column2',
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']]
            ],
            [
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']],
                'column3',
                'column5',
                ['column1' => ['param' => '1'], 'column2' => ['param' => '2'], 'column3' => ['param' => '3']]
            ],
            [
                ['column1' => null, 'column2' => null, 'column3' => null],
                'column1',
                'column2',
                ['column2' => null, 'column1' => null, 'column3' => null]
            ]
        ];
    }

    /**
     * @dataProvider moveFilterBeforeDataProvider
     */
    public function testMoveFilterBefore(array $filters, string $name, string $targetName, array $expectedFilters): void
    {
        $this->configuration->offsetSetByPath('[filters][columns]', $filters);
        $this->configuration->moveFilterBefore($name, $targetName);
        self::assertSame($expectedFilters, $this->configuration->offsetGetByPath('[filters][columns]'));
    }

    public static function moveFilterBeforeDataProvider(): array
    {
        return [
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter3',
                'filter2',
                ['filter1' => ['param' => '1'], 'filter3' => ['param' => '3'], 'filter2' => ['param' => '2']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter3',
                'filter1',
                ['filter3' => ['param' => '3'], 'filter1' => ['param' => '1'], 'filter2' => ['param' => '2']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter5',
                'filter2',
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter3',
                'filter5',
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']]
            ],
            [
                ['filter1' => null, 'filter2' => null, 'filter3' => null],
                'filter3',
                'filter2',
                ['filter1' => null, 'filter3' => null, 'filter2' => null]
            ]
        ];
    }

    /**
     * @dataProvider moveFilterAfterDataProvider
     */
    public function testMoveFilterAfter(array $filters, string $name, string $targetName, array $expectedFilters): void
    {
        $this->configuration->offsetSetByPath('[filters][columns]', $filters);
        $this->configuration->moveFilterAfter($name, $targetName);
        self::assertSame($expectedFilters, $this->configuration->offsetGetByPath('[filters][columns]'));
    }

    public static function moveFilterAfterDataProvider(): array
    {
        return [
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter1',
                'filter2',
                ['filter2' => ['param' => '2'], 'filter1' => ['param' => '1'], 'filter3' => ['param' => '3']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter1',
                'filter3',
                ['filter2' => ['param' => '2'], 'filter3' => ['param' => '3'], 'filter1' => ['param' => '1']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter5',
                'filter2',
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']]
            ],
            [
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']],
                'filter3',
                'filter5',
                ['filter1' => ['param' => '1'], 'filter2' => ['param' => '2'], 'filter3' => ['param' => '3']]
            ],
            [
                ['filter1' => null, 'filter2' => null, 'filter3' => null],
                'filter1',
                'filter2',
                ['filter2' => null, 'filter1' => null, 'filter3' => null]
            ]
        ];
    }
}
