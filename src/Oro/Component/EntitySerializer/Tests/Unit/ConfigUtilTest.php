<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testGetArrayValueWhenConfigDoesNotContainsKey(): void
    {
        $key = 'testKey';
        $config = [];

        self::assertSame([], ConfigUtil::getArrayValue($config, $key));
    }

    public function testGetArrayValueWhenConfigValueIsNull(): void
    {
        $key = 'testKey';
        $config = [
            $key => null
        ];

        self::assertSame([], ConfigUtil::getArrayValue($config, $key));
    }

    public function testGetArrayValueWhenConfigValueIsString(): void
    {
        $key = 'testKey';
        $config = [
            $key => 'testValue'
        ];

        self::assertSame(['testValue' => null], ConfigUtil::getArrayValue($config, $key));
    }

    public function testGetArrayValueWhenConfigValueIsArray(): void
    {
        $key = 'testKey';
        $config = [
            $key => ['key' => 'val']
        ];

        self::assertSame(['key' => 'val'], ConfigUtil::getArrayValue($config, $key));
    }

    public function testGetArrayValueWhenConfigValueIsUnexpectedType(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected value of type "array, string or nothing", "int" given.');

        $key = 'testKey';
        $config = [
            $key => 123
        ];

        ConfigUtil::getArrayValue($config, $key);
    }

    /**
     * @dataProvider getExclusionPolicyProvider
     */
    public function testGetExclusionPolicy(array $config, string $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::getExclusionPolicy($config));
    }

    public function getExclusionPolicyProvider(): array
    {
        return [
            'no exclusion_policy'   => [
                [],
                'none'
            ],
            'exclusion_policy=all'  => [
                ['exclusion_policy' => 'all'],
                'all'
            ],
            'exclusion_policy=none' => [
                ['exclusion_policy' => 'none'],
                'none'
            ],
        ];
    }

    /**
     * @dataProvider isExcludeAllProvider
     */
    public function testIsExcludeAll(array $config, bool $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::isExcludeAll($config));
    }

    public function isExcludeAllProvider(): array
    {
        return [
            'no exclusion_policy'   => [
                [],
                false
            ],
            'exclusion_policy=all'  => [
                ['exclusion_policy' => 'all'],
                true
            ],
            'exclusion_policy=none' => [
                ['exclusion_policy' => 'none'],
                false
            ],
        ];
    }

    /**
     * @dataProvider isExcludeProvider
     */
    public function testIsExclude(array $config, bool $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::isExclude($config));
    }

    public function isExcludeProvider(): array
    {
        return [
            'no exclude'    => [
                [],
                false
            ],
            'exclude=true'  => [
                ['exclude' => true],
                true
            ],
            'exclude=false' => [
                ['exclude' => false],
                false
            ],
        ];
    }

    /**
     * @dataProvider isPartialAllowedProvider
     */
    public function testIsPartialAllowed(array $config, bool $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::isPartialAllowed($config));
    }

    public function isPartialAllowedProvider(): array
    {
        return [
            'no disable_partial_load'    => [
                [],
                true
            ],
            'disable_partial_load=true'  => [
                ['disable_partial_load' => true],
                false
            ],
            'disable_partial_load=false' => [
                ['disable_partial_load' => false],
                true
            ],
        ];
    }

    /**
     * @dataProvider hasFieldConfigProvider
     */
    public function testHasFieldConfig(array $config, string $fieldName, bool $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::hasFieldConfig($config, $fieldName));
    }

    public function hasFieldConfigProvider(): array
    {
        return [
            'no fields'               => [
                [],
                'field1',
                false
            ],
            'field with null config'  => [
                ['fields' => ['field1' => null]],
                'field1',
                true
            ],
            'field with array config' => [
                ['fields' => ['field1' => []]],
                'field1',
                true
            ],
            'not existing field'      => [
                ['fields' => ['field1' => []]],
                'field2',
                false
            ],
        ];
    }

    /**
     * @dataProvider getFieldConfigProvider
     */
    public function testGetFieldConfig(array $config, string $fieldName, array $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::getFieldConfig($config, $fieldName));
    }

    public function getFieldConfigProvider(): array
    {
        return [
            'no fields'               => [
                [],
                'field1',
                []
            ],
            'field with null config'  => [
                ['fields' => ['field1' => null]],
                'field1',
                []
            ],
            'field with array config' => [
                ['fields' => ['field1' => ['property_path' => 'path']]],
                'field1',
                ['property_path' => 'path']
            ],
            'not existing field'      => [
                ['fields' => ['field1' => []]],
                'field2',
                []
            ],
        ];
    }

    /**
     * @dataProvider explodePropertyPathProvider
     */
    public function testExplodePropertyPath(string $propertyPath, array $expectedValue): void
    {
        self::assertSame($expectedValue, ConfigUtil::explodePropertyPath($propertyPath));
    }

    public function explodePropertyPathProvider(): array
    {
        return [
            'empty'                => [
                '',
                ['']
            ],
            'field name'           => [
                'field1',
                ['field1']
            ],
            'path to nested field' => [
                'field1.field11.field111',
                ['field1', 'field11', 'field111']
            ],
        ];
    }

    public function testCloneObjects(): void
    {
        $object1 = new \stdClass();
        $objects = [
            'obj1' => $object1
        ];

        $clonedObjects = ConfigUtil::cloneObjects($objects);

        self::assertEquals($objects, $clonedObjects);
        self::assertNotSame($objects['obj1'], $clonedObjects['obj1']);
    }

    public function testCloneItems(): void
    {
        $value1 = 123;
        $object1 = new \stdClass();
        $items = [
            'val1' => $value1,
            'obj1' => $object1
        ];

        $clonedItems = ConfigUtil::cloneItems($items);

        self::assertEquals($items, $clonedItems);
        self::assertNotSame($items['obj1'], $clonedItems['obj1']);
    }
}
