<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class DatagridConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatagridConfiguration */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = DatagridConfiguration::create([]);
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

    public function testExceptions()
    {
        $this->setExpectedException(
            'BadMethodCallException',
            'DatagridConfiguration::addColumn: name should not be empty'
        );
        $this->configuration->addColumn(null, []);

        $this->setExpectedException(
            'BadMethodCallException',
            'DatagridConfiguration::updateLabel: name should not be empty'
        );
        $this->configuration->updateLabel(null, []);

        $this->setExpectedException(
            'BadMethodCallException',
            'DatagridConfiguration::addSelect: select should not be empty'
        );
        $this->configuration->addSelect(null);
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
        $this->configuration->addSelect('testColumn');

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
        $this->configuration->joinTable('left', ['param' => 'value']);

        $configArray = $this->configuration->toArray();
        $this->assertEquals(
            [
                'source' => [
                    'query' => ['join' => ['left' => [['param' => 'value']]]],
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
