<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Common\DataObject;

class ObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $params
     * @param string $path
     * @param bool $expected
     * @dataProvider getOffsetGetByPathDataProvider
     */
    public function testOffsetGetByPath(array $params, $path, $expected)
    {
        $object = DataObject::create($params);
        $this->assertEquals($expected, $object->offsetExistByPath($path));
    }

    public function getOffsetGetByPathDataProvider()
    {
        $params = [
            'true' => true,
            'false' => false,
            'null' => null,
            'array' => [
                'true' => true,
                'false' => false,
                'null' => null,
            ],
        ];
        return [
            [
                'params' => $params,
                'path' => '[true]',
                'expected' => true,
            ],
            [
                'params' => $params,
                'path' => '[false]',
                'expected' => true,
            ],
            [
                'params' => $params,
                'path' => '[null]',
                'expected' => false,
            ],
            [
                'params' => $params,
                'path' => '[unknown]',
                'expected' => false,
            ],
            [
                'params' => $params,
                'path' => '[array][false]',
                'expected' => true,
            ],
            [
                'params' => $params,
                'path' => '[array][true]',
                'expected' => true,
            ],
            [
                'params' => $params,
                'path' => '[array][null]',
                'expected' => false,
            ],
            [
                'params' => $params,
                'path' => '[array][unknown]',
                'expected' => false,
            ],
        ];
    }
}
