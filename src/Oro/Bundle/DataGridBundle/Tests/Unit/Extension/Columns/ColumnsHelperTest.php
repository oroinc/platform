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
            'name'  => ['order' => 1,'renderable' => false],
            'email' => ['renderable' => false,'order' => 1]
        ];
        $array2 = [
            'email' => ['order' => 1, 'renderable' => false],
            'name' => ['order' => 1,'renderable' => false]
        ];
        $array3 = [
            'email' => ['order' => 1,'renderable' => true],
            'name' => ['order' => 1,'renderable' => false]
        ];
        $array4 = [
            'email' => ['order' => 1,'renderable' => false],
            'name' => ['order' => 0,'renderable' => false]
        ];

        return [
            'equal'     => [
                'viewData' => $array1,
                'urlData'  => $array2,
                'result' => true
            ],
            'not equal' => [
                'viewData'  => $array1,
                'urlData'  => $array3,
                'result' => false
            ],
            'not equal1' => [
                'viewData'  => $array1,
                'urlData'  => $array4,
                'result' => false
            ],
            'not equal2' => [
                'viewData'  => $array3,
                'urlData'  => $array4,
                'result' => false
            ]
        ];
    }
}
