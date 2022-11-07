<?php

namespace Oro\Component\Config\Tests\Unit\Common;

use Oro\Component\Config\Common\ConfigObject;

class ObjectTest extends \PHPUnit\Framework\TestCase
{
    private function getConfigObject(): ConfigObject
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
     * @dataProvider getOffsetExistsDataProvider
     */
    public function testOffsetExists(string $property, bool $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetExists($property));
    }

    public function getOffsetExistsDataProvider(): array
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
     * @dataProvider getOffsetExistByPathDataProvider
     */
    public function testOffsetExistByPath(string $path, bool $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetExistByPath($path));
    }

    public function getOffsetExistByPathDataProvider(): array
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
     * @dataProvider getOffsetGetDataProvider
     */
    public function testOffsetGet(string $property, ?bool $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGet($property));
    }

    public function getOffsetGetDataProvider(): array
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
     * @dataProvider getOffsetGetOrDataProvider
     */
    public function testOffsetGetOr(string $property, ?bool $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetOr($property));
    }

    public function getOffsetGetOrDataProvider(): array
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
     * @dataProvider getOffsetGetByPathDataProvider
     */
    public function testOffsetGetByPath(string $path, ?bool $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetByPath($path));
    }

    public function getOffsetGetByPathDataProvider(): array
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
     * @dataProvider getOffsetGetOrWithDefaultValueDataProvider
     */
    public function testOffsetGetOrWithDefaultValue(string $property, bool|string $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetOr($property, 'default'));
    }

    public function getOffsetGetOrWithDefaultValueDataProvider(): array
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
     * @dataProvider getOffsetGetByPathWithDefaultValueDataProvider
     */
    public function testOffsetGetByPathWithDefaultValue(string $path, bool|string $expected)
    {
        $object = $this->getConfigObject();
        $this->assertEquals($expected, $object->offsetGetByPath($path, 'default'));
    }

    public function getOffsetGetByPathWithDefaultValueDataProvider(): array
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
     * @dataProvider getOffsetSetByPathWithDefaultValueDataProvider
     */
    public function testOffsetSetByPath(string $path)
    {
        $object = $this->getConfigObject();
        $value = 'test';
        $this->assertEquals($value, $object->offsetSetByPath($path, $value)->offsetGetByPath($path));
    }

    public function getOffsetSetByPathWithDefaultValueDataProvider(): array
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
