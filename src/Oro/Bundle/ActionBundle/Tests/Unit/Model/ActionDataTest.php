<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionData;

class ActionDataTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntity()
    {
        $entity = new \stdClass();

        $data = new ActionData(['data' => $entity]);

        $this->assertSame($entity, $data->getEntity());
    }

    public function testGetRedirecturl()
    {
        $data = new ActionData(['redirectUrl' => '@test_url']);

        $this->assertSame('@test_url', $data->getRedirectUrl());
    }

    public function testGetRefreshGrid()
    {
        $data = new ActionData(['refreshGrid' => 'grid-name']);

        $this->assertSame('grid-name', $data->getRefreshGrid());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getValuesProvider
     */
    public function testGetValues(array $inputData, array $expectedData)
    {
        $data = new ActionData($inputData['data']);

        $this->assertEquals($expectedData, $data->getValues($inputData['names']));
    }

    public function testGetScalarValues()
    {
        $data = new ActionData([
            'key1' => ['param1'],
            'key2' => 'value2',
            'key3' => 3,
            'key4' => new \stdClass(),
        ]);

        $this->assertEquals(['key2' => 'value2', 'key3' => 3], $data->getScalarValues());
    }

    /**
     * @return array
     */
    public function getValuesProvider()
    {
        $data = [
            'key1' => ['param1'],
            'key2' => 'value2',
            'key3' => 3,
            'key4' => new \stdClass(),
        ];

        return [
            'full data' => [
                'input' => [
                    'data' => $data,
                    'names' => [],
                ],
                'expected' => $data,
            ],
            'full data' => [
                'input' => [
                    'data' => $data,
                    'names' => ['key1', 'key2', 'unknown_key'],
                ],
                'expected' => [
                    'key1' => ['param1'],
                    'key2' => 'value2',
                    'unknown_key' => null,
                ],
            ],
        ];
    }
}
