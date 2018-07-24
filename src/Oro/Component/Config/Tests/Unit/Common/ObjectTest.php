<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\Common;

use Oro\Component\Config\Common\ConfigObject;

class ObjectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return ConfigObject
     */
    protected function getConfigObject()
    {
        return ConfigObject::create(
            [
                'true'  => true,
                'false' => false,
                'null'  => null,
                'array' => [
                    'true'  => true,
                    'false' => false,
                    'null'  => null,
                ],
            ]
        );
    }

    /**
     * @param string $property
     * @param bool   $expected
     *
     * @dataProvider getOffsetExistsDataProvider
     */
    public function testOffsetExists($property, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetExists($property));
    }

    public function getOffsetExistsDataProvider()
    {
        return [
            [
                'property' => 'true',
                'expected' => true,
            ],
            [
                'property' => 'false',
                'expected' => true,
            ],
            [
                'property' => 'null',
                'expected' => false,
            ],
            [
                'property' => 'unknown',
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $path
     * @param bool   $expected
     *
     * @dataProvider getOffsetExistByPathDataProvider
     */
    public function testOffsetExistByPath($path, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetExistByPath($path));
    }

    public function getOffsetExistByPathDataProvider()
    {
        return [
            [
                'path'     => '[true]',
                'expected' => true,
            ],
            [
                'path'     => '[false]',
                'expected' => true,
            ],
            [
                'path'     => '[null]',
                'expected' => false,
            ],
            [
                'path'     => '[unknown]',
                'expected' => false,
            ],
            [
                'path'     => 'true',
                'expected' => true,
            ],
            [
                'path'     => 'false',
                'expected' => true,
            ],
            [
                'path'     => 'null',
                'expected' => false,
            ],
            [
                'path'     => 'unknown',
                'expected' => false,
            ],
            [
                'path'     => '[array][false]',
                'expected' => true,
            ],
            [
                'path'     => '[array][true]',
                'expected' => true,
            ],
            [
                'path'     => '[array][null]',
                'expected' => false,
            ],
            [
                'path'     => '[array][unknown]',
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $property
     * @param bool   $expected
     *
     * @dataProvider getOffsetGetDataProvider
     */
    public function testOffsetGet($property, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGet($property));
    }

    public function getOffsetGetDataProvider()
    {
        return [
            [
                'property' => 'true',
                'expected' => true,
            ],
            [
                'property' => 'false',
                'expected' => false,
            ],
            [
                'property' => 'null',
                'expected' => null,
            ],
        ];
    }

    /**
     * @param string $property
     * @param bool   $expected
     *
     * @dataProvider getOffsetGetOrDataProvider
     */
    public function testOffsetGetOr($property, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetOr($property));
    }

    public function getOffsetGetOrDataProvider()
    {
        return [
            [
                'property' => 'true',
                'expected' => true,
            ],
            [
                'property' => 'false',
                'expected' => false,
            ],
            [
                'property' => 'null',
                'expected' => null,
            ],
            [
                'property' => 'unknown',
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $path
     * @param bool   $expected
     *
     * @dataProvider getOffsetGetByPathDataProvider
     */
    public function testOffsetGetByPath($path, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetByPath($path));
    }

    public function getOffsetGetByPathDataProvider()
    {
        return [
            [
                'path'     => '[true]',
                'expected' => true,
            ],
            [
                'path'     => '[false]',
                'expected' => false,
            ],
            [
                'path'     => '[null]',
                'expected' => null,
            ],
            [
                'path'     => '[unknown]',
                'expected' => false,
            ],
            [
                'path'     => 'true',
                'expected' => true,
            ],
            [
                'path'     => 'false',
                'expected' => false,
            ],
            [
                'path'     => 'null',
                'expected' => null,
            ],
            [
                'path'     => 'unknown',
                'expected' => false,
            ],
            [
                'path'     => '[array][false]',
                'expected' => false,
            ],
            [
                'path'     => '[array][true]',
                'expected' => true,
            ],
            [
                'path'     => '[array][null]',
                'expected' => null,
            ],
            [
                'path'     => '[array][unknown]',
                'expected' => false,
            ],
        ];
    }

    /**
     * @param string $property
     * @param bool   $expected
     *
     * @dataProvider getOffsetGetOrWithDefaultValueDataProvider
     */
    public function testOffsetGetOrWithDefaultValue($property, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetOr($property, 'default'));
    }

    public function getOffsetGetOrWithDefaultValueDataProvider()
    {
        return [
            [
                'property' => 'true',
                'expected' => true,
            ],
            [
                'property' => 'false',
                'expected' => false,
            ],
            [
                'property' => 'null',
                'expected' => 'default',
            ],
            [
                'property' => 'unknown',
                'expected' => 'default',
            ],
        ];
    }

    /**
     * @param string $path
     * @param bool   $expected
     *
     * @dataProvider getOffsetGetByPathWithDefaultValueDataProvider
     */
    public function testOffsetGetByPathWithDefaultValue($path, $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetByPath($path, 'default'));
    }

    public function getOffsetGetByPathWithDefaultValueDataProvider()
    {
        return [
            [
                'path'     => '[true]',
                'expected' => true,
            ],
            [
                'path'     => '[false]',
                'expected' => false,
            ],
            [
                'path'     => '[null]',
                'expected' => 'default',
            ],
            [
                'path'     => '[unknown]',
                'expected' => 'default',
            ],
            [
                'path'     => 'true',
                'expected' => true,
            ],
            [
                'path'     => 'false',
                'expected' => false,
            ],
            [
                'path'     => 'null',
                'expected' => 'default',
            ],
            [
                'path'     => 'unknown',
                'expected' => 'default',
            ],
            [
                'path'     => '[array][false]',
                'expected' => false,
            ],
            [
                'path'     => '[array][true]',
                'expected' => true,
            ],
            [
                'path'     => '[array][null]',
                'expected' => 'default',
            ],
            [
                'path'     => '[array][unknown]',
                'expected' => 'default',
            ],
        ];
    }

    /**
     * @param string $path
     *
     * @dataProvider getOffsetSetByPathWithDefaultValueDataProvider
     */
    public function testOffsetSetByPath($path)
    {
        $object = $this->getConfigObject();
        $value = 'test';
        $this->assertEquals($value, $object->offsetSetByPath($path, $value)->offsetGetByPath($path));
    }

    public function getOffsetSetByPathWithDefaultValueDataProvider()
    {
        return [
            [
                'path' => '[true]',
            ],
            [
                'path' => '[false]',
            ],
            [
                'path' => '[null]',
            ],
            [
                'path' => '[unknown]',
            ],
            [
                'path' => 'true',
            ],
            [
                'path' => 'false',
            ],
            [
                'path' => 'null',
            ],
            [
                'path' => 'unknown',
            ],
            [
                'path' => '[array][false]',
            ],
            [
                'path' => '[array][true]',
            ],
            [
                'path' => '[array][null]',
            ],
            [
                'path' => '[array][unknown]',
            ],
        ];
    }
}
