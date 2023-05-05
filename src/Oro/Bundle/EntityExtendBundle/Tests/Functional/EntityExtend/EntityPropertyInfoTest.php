<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Stub\TestEnum;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityPropertyInfoTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->bootKernel();
    }

    /**
     * @dataProvider methodExistsDataProvider
     */
    public function testMethodExists(string $method, string|object $class, bool $expectedResult): void
    {
        $isMethodExists = EntityPropertyInfo::methodExists($class, $method);

        self::assertSame($isMethodExists, $expectedResult);
    }

    public function methodExistsDataProvider(): array
    {
        return [
            'method does not exists' => [
                'name' => 'getUndefinedMethodName',
                'class' => User::class,
                'expectedResult' => false
            ],
            'real method exists' => [
                'name' => 'getUserName',
                'class' => User::class,
                'expectedResult' => true
            ],
            'extend method exists for object' => [
                'name' => 'getPhone',
                'class' => new User(),
                'expectedResult' => true
            ],
            'attribute method exists' => [
                'name' => 'getImage',
                'class' => AttributeFamily::class,
                'expectedResult' => true
            ],
            'serialized_data method exists' => [
                'name' => 'getSerializedData',
                'class' => AttributeFamily::class,
                'expectedResult' => true
            ],
            'method exists with broken register' => [
                'name' => 'isDeFaUlt',
                'class' => TestEnum::class,
                'expectedResult' => true
            ],
            'method exists for object' => [
                'name' => 'isDefault',
                'class' => new TestEnum(1, 'test'),
                'expectedResult' => true
            ],
        ];
    }

    /**
     * @dataProvider propertyExistsDataProvider
     */
    public function testPropertyExists(string $method, string|object $class, bool $expectedResult): void
    {
        $isMethodExists = EntityPropertyInfo::propertyExists($class, $method);

        self::assertSame($isMethodExists, $expectedResult);
    }

    public function propertyExistsDataProvider(): array
    {
        return [
            'property does not exists' => [
                'name' => 'undefinedProperty',
                'class' => User::class,
                'expectedResult' => false
            ],
            'real protected property exists' => [
                'name' => 'locale',
                'class' => TestEnum::class,
                'expectedResult' => true
            ],
            'extend property exists for object' => [
                'name' => 'phone',
                'class' => new User(),
                'expectedResult' => true
            ],
            'extend file type property exists' => [
                'name' => 'image',
                'class' => AttributeFamily::class,
                'expectedResult' => true
            ],
            'serialized_data property exists' => [
                'name' => 'serialized_data',
                'class' => AttributeFamily::class,
                'expectedResult' => true
            ],
            'protected property exists with object' => [
                'name' => 'locale',
                'class' => new TestEnum(1, 'test'),
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
                'class' => User::class,
                'extendedProperties' => [
                    'phone',
                    'title'
                ]
            ],
            'attribute properties' => [
                'class' => AttributeFamily::class,
                'extendedProperties' => [
                    'image',
                ]
            ],
            'serialized_data properties' => [
                'class' => AttributeFamily::class,
                'extendedProperties' => [
                    'serialized_data',
                ]
            ],
        ];
    }

    public function testGetExtendedPropertiesWithoutReal(): void
    {
        $extendedProperties = EntityPropertyInfo::getExtendedProperties(User::class);
        // real properties
        self::assertNotContains('username', $extendedProperties);
        self::assertNotContains('email', $extendedProperties);
        self::assertNotContains('firstName', $extendedProperties);
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
            self::assertTrue(isset($extendedMethods[$method]));
        }
    }

    public function extendedMethodsDataProvider(): array
    {
        return [
            'extended methods' => [
                'class' => User::class,
                'extendedMethods' => [
                    'getPhone',
                    'setPhone',
                    'getTitle',
                    'setTitle'
                ]
            ],
            'attribute properties' => [
                'class' => AttributeFamily::class,
                'extendedMethods' => [
                    'getImage',
                    'setImage',
                ]
            ],
            'serialized_data properties' => [
                'class' => AttributeFamily::class,
                'extendedMethods' => [
                    'getSerializedData',
                ]
            ],
        ];
    }

    public function testGetExtendedMethodsWithoutReal(): void
    {
        $extendedMethods = EntityPropertyInfo::getExtendedMethods(User::class);
        // real properties
        self::assertNotContains('getUsername', $extendedMethods);
        self::assertNotContains('setEmail', $extendedMethods);
        self::assertNotContains('getFirstName', $extendedMethods);
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
                'class' => User::class,
                'realMethod' => 'GETEMAIL',
                'expectedResult' => 'GETEMAIL',
            ],
            'matched method for extended property return correct method name' => [
                'class' => User::class,
                'realMethod' => 'getphone',
                'expectedResult' => 'getPhone',
            ],
            'matched serialized_data method' => [
                'class' => User::class,
                'realMethod' => 'getSERializEdData',
                'expectedResult' => 'getSerializedData',
            ],
        ];
    }
}
