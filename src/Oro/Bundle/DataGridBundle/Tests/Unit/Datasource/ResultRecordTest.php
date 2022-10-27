<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Tests\Unit\Stub\ValueContainer;

class ResultRecordTest extends \PHPUnit\Framework\TestCase
{
    public function testAddDataObject()
    {
        $record = new ResultRecord(['key' => 'value']);
        $record->addData(new ValueContainer('other value'));

        $this->assertSame('value', $record->getValue('key'));
        $this->assertSame('other value', $record->getValue('something'));
    }

    public function testAddDataArrayOfObjectsWithNumericIndices()
    {
        $record = new ResultRecord(['key' => 'value']);
        $record->addData([new ValueContainer('other value')]);

        $this->assertSame('value', $record->getValue('key'));
        $this->assertSame('other value', $record->getValue('something'));
    }

    /**
     * @dataProvider getValueProvider
     */
    public function testGetValue(mixed $data, string $itemName, mixed $expectedValue)
    {
        $resultRecord = new ResultRecord($data);

        $this->assertEquals($expectedValue, $resultRecord->getValue($itemName));
    }

    public function getValueProvider(): array
    {
        $obj = new \stdClass();
        $obj->item1 = 'val1';

        $dateTime = new \DateTime();

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
            [
                'data'          => [$dateTime],
                'itemName'      => 'timestamp',
                'expectedValue' => $dateTime->getTimestamp()
            ],
            [
                'data'          => [$dateTime],
                'itemName'      => 'qwerty',
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @dataProvider getRootEntityProvider
     */
    public function testGetRootEntity(mixed $data, mixed $expectedValue)
    {
        $resultRecord = new ResultRecord($data);

        $this->assertEquals($expectedValue, $resultRecord->getRootEntity());
    }

    public function getRootEntityProvider(): array
    {
        $obj = new \stdClass();
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
    public function testSetValue(mixed $data, string $itemName, mixed $itemValue)
    {
        $resultRecord = new ResultRecord($data);

        $resultRecord->setValue($itemName, $itemValue);

        $this->assertEquals($itemValue, $resultRecord->getValue($itemName));
    }

    public function setValueProvider(): array
    {
        $obj = new \stdClass();
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
