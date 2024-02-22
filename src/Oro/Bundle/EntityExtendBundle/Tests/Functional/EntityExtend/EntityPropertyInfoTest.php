<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Extend\Entity\EV_Test_Extended_Entity_Enum_Attribute;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityPropertyInfoTest extends WebTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider methodExistsDataProvider
     */
    public function testMethodExists(string $method, string|object $class, bool $expectedResult): void
    {
        if (is_callable($class)) {
            $class = $class();
        }
        $isMethodExists = EntityPropertyInfo::methodExists($class, $method);

        self::assertSame($isMethodExists, $expectedResult);
    }

    public function methodExistsDataProvider(): array
    {
        return [
            'method does not exists' => [
                'name' => 'getUndefinedMethodName',
                'class' => TestExtendedEntity::class,
                'expectedResult' => false
            ],
            'real method exists' => [
                'name' => 'getRegularField',
                'class' => TestExtendedEntity::class,
                'expectedResult' => true
            ],
            'extend method exists for object' => [
                'name' => 'getName',
                'class' => fn () => new TestExtendedEntity(),
                'expectedResult' => true
            ],
            'attribute method exists' => [
                'name' => 'getTestExtendedEntityEnumAttribute',
                'class' => TestExtendedEntity::class,
                'expectedResult' => true
            ],
            'serialized_data method exists' => [
                'name' => 'getSerializedData',
                'class' => TestExtendedEntity::class,
                'expectedResult' => true
            ],
            'method exists with broken register' => [
                'name' => 'isDeFaUlt',
                'class' => EV_Test_Extended_Entity_Enum_Attribute::class,
                'expectedResult' => true
            ],
            'method exists for object' => [
                'name' => 'isDefault',
                'class' => fn () => new EV_Test_Extended_Entity_Enum_Attribute(1, 'test'),
                'expectedResult' => true
            ],
            'serialized attribute get method not exists' => [
                'name' => 'getSerializedAttribute',
                'class' => TestExtendedEntity::class,
                'expectedResult' => false
            ],
            'serialized attribute set method not exists' => [
                'name' => 'setSerializedAttribute',
                'class' => TestExtendedEntity::class,
                'expectedResult' => false
            ]
        ];
    }

    /**
     * @dataProvider propertyExistsDataProvider
     */
    public function testPropertyExists(string $method, string|callable $class, bool $expectedResult): void
    {
        if (is_callable($class)) {
            $class = $class();
        }
        $isMethodExists = EntityPropertyInfo::propertyExists($class, $method);

        self::assertSame($isMethodExists, $expectedResult);
    }

    public function propertyExistsDataProvider(): array
    {
        return [
            'property does not exists' => [
                'name' => 'undefinedProperty',
                'class' => TestExtendedEntity::class,
                'expectedResult' => false
            ],
            'real protected property exists' => [
                'name' => 'locale',
                'class' => EV_Test_Extended_Entity_Enum_Attribute::class,
                'expectedResult' => true
            ],
            'extend property exists for object' => [
                'name' => 'name',
                'class' => fn () => new TestExtendedEntity(),
                'expectedResult' => true
            ],
            'extend file type property exists' => [
                'name' => 'testExtendedEntityEnumAttribute',
                'class' => TestExtendedEntity::class,
                'expectedResult' => true
            ],
            'serialized_data property exists' => [
                'name' => 'serialized_data',
                'class' => TestExtendedEntity::class,
                'expectedResult' => true
            ],
            'protected property exists with object' => [
                'name' => 'locale',
                'class' => fn () => new EV_Test_Extended_Entity_Enum_Attribute(1, 'test'),
                'expectedResult' => true
            ],
        ];
    }

    public function testGetExtendedPropertiesEmpty(): void
    {
        $extendedProperties = EntityPropertyInfo::getExtendedProperties(AbstractEnumValue::class);

        self::assertSame($extendedProperties, []);
    }

    /**
     * @dataProvider extendedPropertiesDataProvider
     */
    public function testGetExtendedProperties(string|object $class, array $expectedResult): void
    {
        $extendedProperties = EntityPropertyInfo::getExtendedProperties($class);
        foreach ($expectedResult as $extendedProperty) {
            self::assertContains($extendedProperty, $extendedProperties);
        }
    }

    public function extendedPropertiesDataProvider(): array
    {
        return [
            'extended properties' => [
                'class' => TestExtendedEntity::class,
                'extendedProperties' => [
                    'name',
                    'testExtendedEntityEnumAttribute'
                ]
            ],
            'attribute properties' => [
                'class' => TestExtendedEntity::class,
                'extendedProperties' => [
                    'testExtendedEntityEnumAttribute',
                ]
            ],
            'serialized_data properties' => [
                'class' => TestExtendedEntity::class,
                'extendedProperties' => [
                    'serialized_data',
                ]
            ],
        ];
    }

    public function testGetExtendedPropertiesWithoutReal(): void
    {
        $extendedProperties = EntityPropertyInfo::getExtendedProperties(TestExtendedEntity::class);
        // real property
        self::assertNotContains('regularField', $extendedProperties);
    }

    public function testGetExtendedMethodsEmpty(): void
    {
        $extendedProperties = EntityPropertyInfo::getExtendedMethods(AbstractEnumValue::class);

        self::assertSame($extendedProperties, []);
    }

    /**
     * @dataProvider extendedMethodsDataProvider
     */
    public function testGetExtendedMethods(string|object $class, array $expectedResult): void
    {
        $extendedMethods = EntityPropertyInfo::getExtendedMethods($class);
        foreach ($expectedResult as $method) {
            self::assertTrue(in_array($method, $extendedMethods, true));
        }
    }

    public function extendedMethodsDataProvider(): array
    {
        return [
            'extended methods' => [
                'class' => TestExtendedEntity::class,
                'extendedMethods' => [
                    'getName',
                    'setName',
                    'getTestExtendedEntityEnumAttribute',
                    'setTestExtendedEntityEnumAttribute'
                ]
            ],
            'attribute properties' => [
                'class' => TestExtendedEntity::class,
                'extendedMethods' => [
                    'getTestExtendedEntityEnumAttribute',
                    'setTestExtendedEntityEnumAttribute',
                ]
            ],
            'serialized_data properties' => [
                'class' => TestExtendedEntity::class,
                'extendedMethods' => [
                    'getSerializedData',
                ]
            ],
        ];
    }

    /**
     * @dataProvider extendedMethodInfoDataProvider
     */
    public function testGetExtendedMethodInfo(string|object $class, string $method, array $expectedResult): void
    {
        $methodInfo = EntityPropertyInfo::getExtendedMethodInfo($class, $method);

        foreach ($expectedResult as $expectedKey) {
            self::assertArrayHasKey($expectedKey, $methodInfo);
        }
    }

    public function extendedMethodInfoDataProvider(): array
    {
        return [
            'extended methods' => [
                'class' => TestExtendedEntity::class,
                'method' => 'getName',
                'expectedResult' => [
                    'fieldName',
                    'fieldType',
                    'is_extend',
                    'is_nullable',
                    'is_serialized',
                ]
            ],
            'attribute properties' => [
                'class' => TestExtendedEntity::class,
                'method' => 'getTestExtendedEntityEnumAttribute',
                'expectedResult' => [
                    'fieldName',
                    'fieldType',
                    'is_extend',
                    'is_nullable',
                    'is_serialized',
                ]
            ],
            'serialized_data properties' => [
                'class' => TestExtendedEntity::class,
                'method' => 'getSerializedData',
                'expectedResult' => []
            ],
        ];
    }

    public function testGetExtendedMethodsWithoutReal(): void
    {
        $extendedMethods = EntityPropertyInfo::getExtendedMethods(TestExtendedEntity::class);
        // real properties
        self::assertNotContains('getRegularField', $extendedMethods);
        self::assertNotContains('setRegularField', $extendedMethods);
    }

    /**
     * @dataProvider isMethodMatchExistsDataProvider
     */
    public function testIsMethodMatchExists(string $methodCandidate, array $methods, bool $expectedResult): void
    {
        $isMethodMatchExists = EntityPropertyInfo::isMethodMatchExists($methods, $methodCandidate);

        self::assertSame($isMethodMatchExists, $expectedResult);
    }

    public function isMethodMatchExistsDataProvider(): array
    {
        return [
            'method match exists with first uppercase' => [
                'methodCandidate' => 'DefaultValue',
                'methods' => [
                    'name',
                    'priority',
                    'action',
                    'defaultValue'
                ],
                'expectedResult' => true
            ],
            'method match is not exists' => [
                'methodCandidate' => 'undefinedMethodName',
                'methods' => [
                    'name',
                    'defaultValue'
                ],
                'expectedResult' => false
            ],
            'method match exists with full uppercase' => [
                'methodCandidate' => 'DEFAULTVALUE',
                'methods' => [
                    'name',
                    'defaultValue'
                ],
                'expectedResult' => true
            ],
            'method match exists with lowercase' => [
                'methodCandidate' => 'defaultvalue',
                'methods' => [
                    'name',
                    'defaultValue'
                ],
                'expectedResult' => true
            ],
        ];
    }

    /**
     * @dataProvider getMatchedMethodDataProvider
     */
    public function testGetMatchedExtendMethod(string $class, string $realMethod, mixed $expectedResult): void
    {
        $matchedMethod = EntityPropertyInfo::getMatchedMethod($class, $realMethod);

        self::assertSame($expectedResult, $matchedMethod);
    }

    public function getMatchedMethodDataProvider(): array
    {
        return [
            'matched method for real property return the same' => [
                'class' => TestExtendedEntity::class,
                'realMethod' => 'GETREGULARFIELD',
                'expectedResult' => 'GETREGULARFIELD',
            ],
            'matched method for extended property return correct method name' => [
                'class' => TestExtendedEntity::class,
                'realMethod' => 'getName',
                'expectedResult' => 'getName',
            ],
            'matched serialized_data method' => [
                'class' => TestExtendedEntity::class,
                'realMethod' => 'getSERializEdData',
                'expectedResult' => 'getSerializedData',
            ],
        ];
    }
}
