<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Columns;

use Oro\Bundle\DataGridBundle\Tools\ColumnsHelper;

class ColumnsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var   ColumnsHelper*/
    protected $columnsHelper;

    protected function setUp()
    {
        $this->columnsHelper = new ColumnsHelper();
    }

    /**
     * @param array  $columnsConfigArray
     * @param string $columns
     * @param array  $result
     *
     * @dataProvider prepareColumnsParamProvider
     */
    public function testPrepareColumnsParam($columnsConfigArray, $columns, $result)
    {
        $columnsData = $this->columnsHelper->prepareColumnsParam($columnsConfigArray, $columns);
        static::assertEquals($columnsData, $result);
    }

    /**
     * @return array
     */
    public function prepareColumnsParamProvider()
    {
        $columnsConfig = [
            'name'  => ['order' => 0,'renderable' => true, 'name' => 'Name'],
            'email' => ['order' => 1,'renderable' => true, 'name' => 'Email'],
            'data'  => ['order' => 2,'renderable' => true, 'name' => 'Data'],
            'city'  => ['order' => 3,'renderable' => true, 'name' => 'City']
        ];

        $columnsString1 = 'name1.email1.data1.city1';
        $columnsString2 = 'city1.name1.email1.data1';
        $columnsString3 = 'city1.name0.email1.data1';

        $columnsArray = [
            'name'  => ['order' => '2','renderable' => 'true', 'name' => 'Name'],
            'email' => ['order' => '0','renderable' => 'true', 'name' => 'Email'],
            'data'  => ['order' => '1','renderable' => 'true', 'name' => 'Data'],
            'city'  => ['order' => '3','renderable' => 'false','name' => 'City']
        ];

        $result1 = [
            'name'  => ['order' => 0,'renderable' => true, 'name' => 'Name'],
            'email' => ['order' => 1,'renderable' => true, 'name' => 'Email'],
            'data'  => ['order' => 2,'renderable' => true, 'name' => 'Data'],
            'city'  => ['order' => 3,'renderable' => true, 'name' => 'City']
        ];
        $result2 = [
            'name'  => ['order' => 1,'renderable' => true, 'name' => 'Name'],
            'email' => ['order' => 2,'renderable' => true, 'name' => 'Email'],
            'data'  => ['order' => 3,'renderable' => true, 'name' => 'Data'],
            'city'  => ['order' => 0,'renderable' => true, 'name' => 'City']
        ];

        $result3 = [
            'name'  => ['order' => 1,'renderable' => false,'name' => 'Name'],
            'email' => ['order' => 2,'renderable' => true, 'name' => 'Email'],
            'data'  => ['order' => 3,'renderable' => true, 'name' => 'Data'],
            'city'  => ['order' => 0,'renderable' => true, 'name' => 'City']
        ];

        $result4 = [
            'name'  => ['order' => 2,'renderable' => true, 'name' => 'Name'],
            'email' => ['order' => 0,'renderable' => true, 'name' => 'Email'],
            'data'  => ['order' => 1,'renderable' => true, 'name' => 'Data'],
            'city'  => ['order' => 3,'renderable' => false,'name' => 'City']
        ];

        return [
            'default grid state'     => [
                'columnsConfigArray' => $columnsConfig,
                'columnsString'      => $columnsString1,
                'result'             => $result1
            ],
            'sorted grid' => [
                'columnsConfigArray' => $columnsConfig,
                'columnsString'      => $columnsString2,
                'result'             => $result2
            ],
            'sorted grid with not renderable columns' => [
                'columnsConfigArray' => $columnsConfig,
                'columnsString'      => $columnsString3,
                'result'             => $result3
            ],
            'sorted grid with not renderable columns not minified params' => [
                'columnsConfigArray' => $columnsConfig,
                'columnsString'      => $columnsArray,
                'result'             => $result4
            ]
        ];
    }

    /**
     * @param array $viewData
     * @param array $urlData
     * @param array $result
     *
     * @dataProvider compareColumnsDataProvider
     */
    public function testCompareColumnsData($viewData, $urlData, $result)
    {
        $isEqual = $this->columnsHelper->compareColumnsData($viewData, $urlData);
        static::assertEquals(
            $isEqual,
            $result
        );
    }

    /**
     * @return array
     */
    public function compareColumnsDataProvider()
    {
        $array1 = [
            'name'  => ['order' => 1,'renderable' => true],
            'email' => ['renderable' => true,'order' => 2]
        ];
        $array2 = [
            'email' => ['order' => 2, 'renderable' => true],
            'name'  => ['order' => 1, 'renderable' => true]
        ];
        $array3 = [
            'email' => ['order' => 1, 'renderable' => true],
            'name'  => ['order' => 2, 'renderable' => false]
        ];
        $array4 = [
            'email' => ['order' => 1, 'renderable' => false],
            'name'  => ['order' => 0, 'renderable' => false]
        ];

        $array5 = [
            'email' => ['order' => 1, 'renderable' => false],
            'name2' => ['order' => 1, 'renderable' => false]
        ];

        $array6 = [
            'email' => ['order' => 2, 'renderable' => true],
            'name'  => ['order' => 1, 'renderable' => true],
            'name2' => ['order' => 3, 'renderable' => true],
        ];

        return [
            'equal' => [
                'viewData' => $array1,
                'urlData'  => $array2,
                'result'   => true
            ],
            'not equal' => [
                'viewData' => $array1,
                'urlData'  => $array3,
                'result'   => false
            ],
            'not equal1' => [
                'viewData' => $array1,
                'urlData'  => $array4,
                'result'   => false
            ],
            'not equal2' => [
                'viewData' => $array3,
                'urlData'  => $array4,
                'result'   => false
            ],
            'not equal3' => [
                'viewData' => $array2,
                'urlData'  => $array5,
                'result'   => false
            ],
            'not equal keys' => [
                'viewData' => $array2,
                'urlData'  => $array6,
                'result'   => false
            ],
            'not valid data2' => [
                'viewData' => $array1,
                'urlData'  => [],
                'result'   => false
            ],
        ];
    }

    /**
     * @param array $columnsArray
     * @param array $result
     *
     * @dataProvider buildColumnsOrderProvider
     */
    public function testBuildColumnsOrder($columnsArray, $result)
    {
        $isEqual = $this->columnsHelper->buildColumnsOrder($columnsArray);
        static::assertEquals(
            $isEqual,
            $result
        );
    }

    /**
     * @return array
     */
    public function buildColumnsOrderProvider()
    {
        $columns1 = [
            'name'  => ['order' => 3, 'name' => 'Name'],
            'email' => ['name' => 'Email'],
            'data'  => ['name' => 'Data'],
            'city'  => ['name' => 'City']
        ];
        $columns2 = [
            'name'  => ['name' => 'Name'],
            'email' => ['name' => 'Email'],
            'data'  => ['name' => 'Data'],
            'city'  => ['name' => 'City']
        ];

        $result1 = [
            'name'  => 3,
            'email' => 0,
            'data'  => 1,
            'city'  => 2
        ];
        $result2 = [
            'name'  => 0,
            'email' => 1,
            'data'  => 2,
            'city'  => 3
        ];

        return [
            'columns have default config'          => [
                'columnsArray' => $columns1,
                'result'       => $result1
            ],
            'columns does not have default config' => [
                'columnsArray' => $columns2,
                'result'       => $result2
            ]
        ];
    }

    /**
     * Test reorder column for export grid
     *
     * @param array  $columnsArray
     * @param string $columnsParams
     * @param array  $result
     *
     * @dataProvider reorderColumnsProvider
     */
    public function testReorderColumns($columnsArray, $columnsParams, $result)
    {
        $isEqual = $this->columnsHelper->reorderColumns($columnsArray, $columnsParams);
        static::assertEquals(
            $isEqual,
            $result
        );
    }

    /**
     * @return array
     */
    public function reorderColumnsProvider()
    {
        $columnsParams1 = 'name1.email1.data1.city1';
        $columnsParams2 = 'email1.data1.city1.name1';
        $columnsParams3 = 'city1.name1.email1.data1';

        $columns1 = [
            'name'  => ['label' => 'Name'],
            'email' => ['label' => 'Email'],
            'data'  => ['label' => 'Data'],
            'city'  => ['label' => 'City'],
        ];

        $columns2 = [
            'name'  => ['label' => 'Name',  'renderable' => true],
            'email' => ['label' => 'Email', 'renderable' => true],
            'data'  => ['label' => 'Data',  'order' => 2, 'renderable' => true],
            'city'  => ['label' => 'City',  'renderable' => true]
        ];

        $columns3 = [
            'name'  => ['label' => 'Name',  'renderable' => true],
            'email' => ['label' => 'Email', 'renderable' => true],
            'data'  => ['label' => 'Data',  'renderable' => true],
            'city'  => ['label' => 'City',  'renderable' => true]
        ];

        $result1 = [
            'name'  => ['label' => 'Name',  'order' => 0, 'renderable' => true],
            'email' => ['label' => 'Email', 'order' => 1, 'renderable' => true],
            'data'  => ['label' => 'Data',  'order' => 2, 'renderable' => true],
            'city'  => ['label' => 'City',  'order' => 3, 'renderable' => true],
        ];

        $result2 = [
            'email' => ['label' => 'Email', 'order' => 0, 'renderable' => true],
            'data'  => ['label' => 'Data',  'order' => 1, 'renderable' => true],
            'city'  => ['label' => 'City',  'order' => 2, 'renderable' => true],
            'name'  => ['label' => 'Name',  'order' => 3, 'renderable' => true],
        ];

        $result3 = [
            'city'  => ['label' => 'City',  'order' => 0, 'renderable' => true],
            'name'  => ['label' => 'Name',  'order' => 1, 'renderable' => true],
            'email' => ['label' => 'Email', 'order' => 2, 'renderable' => true],
            'data'  => ['label' => 'Data',  'order' => 3, 'renderable' => true],
        ];

        return [
            'default order' => [
                'columnsArray'  => $columns1,
                'columnsParams' => $columnsParams1,
                'result'        => $result1
            ],
            'columns without order' => [
                'columnsArray'  => $columns2,
                'columnsParams' => $columnsParams2,
                'result'        => $result2
            ],
            'some columns have default order' => [
                'columnsArray'  => $columns3,
                'columnsParams' => $columnsParams3,
                'result'        => $result3
            ]
        ];
    }
}
