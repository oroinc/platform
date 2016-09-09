<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class ResultRecordTest extends \PHPUnit_Framework_TestCase
{
    public function testAddData()
    {
        $originalContainer   = ['first' => 1];
        $additionalContainer = ['second' => 2];

        $resultRecord = new ResultRecord($originalContainer);
        $resultRecord->addData($additionalContainer);

        $this->assertAttributeContains($originalContainer, 'valueContainers', $resultRecord);
        $this->assertAttributeContains($additionalContainer, 'valueContainers', $resultRecord);

        $this->assertEquals($originalContainer['first'], $resultRecord->getValue('first'));
        $this->assertEquals($additionalContainer['second'], $resultRecord->getValue('second'));
    }

    /**
     * @dataProvider getValueProvider
     */
    public function testGetValue($data, $itemName, $expectedValue)
    {
        $resultRecord = new ResultRecord($data);

        $this->assertEquals($expectedValue, $resultRecord->getValue($itemName));
    }

    public function getValueProvider()
    {
        $obj        = new \stdClass();
        $obj->item1 = 'val1';

        return [
            [
                'data'          => [],
                'itemName'      => 'item1',
                'expectedValue' => null
            ],
            [
                'data'          => ['item1' => 'val1'],
                'itemName'      => 'item1',
                'expectedValue' => 'val1'
            ],
            [
                'data'          => $obj,
                'itemName'      => 'item1',
                'expectedValue' => 'val1'
            ],
            [
                'data'          => $obj,
                'itemName'      => 'item_1',
                'expectedValue' => 'val1'
            ],
            [
                'data'          => [],
                'itemName'      => '[item1][subItem1]',
                'expectedValue' => null
            ],
            [
                'data'          => ['item1' => []],
                'itemName'      => '[item1][subItem1]',
                'expectedValue' => null
            ],
            [
                'data'          => ['item1' => ['subItem1' => 'val1']],
                'itemName'      => '[item1][subItem1]',
                'expectedValue' => 'val1'
            ],
        ];
    }

    /**
     * @dataProvider getRootEntityProvider
     */
    public function testGetRootEntity($data, $expectedValue)
    {
        $resultRecord = new ResultRecord($data);

        $this->assertEquals($expectedValue, $resultRecord->getRootEntity());
    }

    public function getRootEntityProvider()
    {
        $obj        = new \stdClass();
        $obj->item1 = 'val1';

        return [
            [
                'data'          => [],
                'expectedValue' => null
            ],
            [
                'data'          => ['item1' => 'val1'],
                'expectedValue' => null
            ],
            [
                'data'          => $obj,
                'expectedValue' => $obj
            ],
            [
                'data'          => [['item1' => 'val1'], $obj],
                'expectedValue' => $obj
            ],
        ];
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetValue($data, $itemName, $itemValue)
    {
        $resultRecord = new ResultRecord($data);

        $resultRecord->setValue($itemName, $itemValue);

        $this->assertEquals($itemValue, $resultRecord->getValue($itemName));
    }
    
    public function setValueProvider()
    {
        $obj        = new \stdClass();
        $obj->item1 = 'val1';

        return [
            [
                'data'          => ['item1' => 'val1'],
                'itemName'      => 'item1',
                'itemValue' => '123'
            ],
            [
                'data'          => $obj,
                'itemName'      => 'item1',
                'itemValue' => 'test'
            ],
            [
                'data'          => $obj,
                'itemName'      => 'item_1',
                'itemValue' => '789'
            ],
            [
                'data'          => ['item1' => []],
                'itemName'      => '[item1][subItem1]',
                'itemValue' => 123
            ],
            [
                'data'          => ['item1' => ['subItem1' => 'val1']],
                'itemName'      => '[item1][subItem1]',
                'itemValue' => 'test'
            ],
        ];
    }
}
