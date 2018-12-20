<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;

class DatagridConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridConfiguration */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = DatagridConfiguration::create([]);
    }

    public function testGetOrmQueryForUndefinedDatasourceType()
    {
        self::assertInstanceOf(OrmQueryConfiguration::class, $this->configuration->getOrmQuery());
    }

    public function testGetOrmQueryForOrmDatasourceType()
    {
        $this->configuration->setDatasourceType(OrmDatasource::TYPE);
        self::assertInstanceOf(OrmQueryConfiguration::class, $this->configuration->getOrmQuery());
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\LogicException
     * @expectedExceptionMessage The expected data grid source type is "orm". Actual source type is "another".
     */
    public function testGetOrmQueryForNotOrmDatasourceType()
    {
        $this->configuration->setDatasourceType('another');
        $this->configuration->getOrmQuery();
    }

    public function testIsOrmDatasource()
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

    public function testDatasourceType()
    {
        // test initial value
        self::assertNull($this->configuration->getDatasourceType());
        // test setter
        self::assertSame($this->configuration, $this->configuration->setDatasourceType('test'));
        // test previously set value
        self::assertEquals('test', $this->configuration->getDatasourceType());
    }

    public function testExtendedEntityClassName()
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
     * @param array $params
     * @param bool $expected
     * @dataProvider getAclResourceDataProvider
     */
    public function testGetAclResource(array $params, $expected)
    {
        $this->configuration->merge($params);
        $this->assertEquals($expected, $this->configuration->getAclResource());
    }

    public function getAclResourceDataProvider()
    {
        return [
            [
                'params' => [
                    'acl_resource' => false,
                    'source' => ['acl_resource' => false],
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'acl_resource' => false,
                    'source' => ['acl_resource' => true],
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'acl_resource' => true,
                    'source' => ['acl_resource' => false],
                ],
                'expected' => true,
            ],
            [
                'params' => [
                    'acl_resource' => true,
                    'source' => ['acl_resource' => true],
                ],
                'expected' => true,
            ],
            [
                'params' => ['acl_resource' => true],
                'expected' => true,
            ],
            [
                'params' => [
                    'acl_resource' => false,
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'source' => ['acl_resource' => false],
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'source' => ['acl_resource' => true],
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $params
     * @param bool $expected
     * @dataProvider isDatasourceSkipAclApplyDataProvider
     */
    public function testIsDatasourceSkipAclApply(array $params, $expected)
    {
        $this->configuration->merge($params);
        $this->assertEquals($expected, $this->configuration->isDatasourceSkipAclApply());
    }

    public function isDatasourceSkipAclApplyDataProvider()
    {
        return [
            [
                'params' => [
                    'source' => [
                        'skip_acl_apply' => false,
                        'skip_acl_check' => false,
                    ],
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'source' => [
                        'skip_acl_apply' => false,
                        'skip_acl_check' => true,
                    ],
                ],
                'expected' => false,
            ],
            [
                'params' => [
                    'source' => [
                        'skip_acl_apply' => true,
                        'skip_acl_check' => false,
                    ],
                ],
                'expected' => true,
            ],
            [
                'params' => [
                    'source' => [
                        'skip_acl_apply' => true,
                        'skip_acl_check' => true,
                    ],
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider addColumnDataProvider
     *
     * @param array  $expected
     * @param string $name
     * @param string $select
     * @param array  $definition
     * @param array  $sorter
     * @param array  $filter
     */
    public function testAddColumn(
        $expected,
        $name,
        $definition,
        $select = null,
        $sorter = [],
        $filter = []
    ) {
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

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage DatagridConfiguration::addColumn: name should not be empty
     */
    public function testAddColumnWithoutName()
    {
        $this->configuration->addColumn(null, []);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage DatagridConfiguration::updateLabel: name should not be empty
     */
    public function testUpdateLabelWithoutName()
    {
        $this->configuration->updateLabel(null, []);
    }

    public function testUpdateLabel()
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

    public function testAddSelect()
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

    public function testJoinTable()
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

    public function testRemoveColumn()
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

    public function testIsDatagridExtendedFrom()
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

    /**
     * @return array
     */
    public function addColumnDataProvider()
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
}
