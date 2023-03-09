<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Stub\TestEnum;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityPropertyInfoTest extends WebTestCase
{
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
                'name' => 'getDefault',
                'class' => TestEnum::class,
                'expectedResult' => false
            ],
            'method exists' => [
                'name' => 'isDefault',
                'class' => TestEnum::class,
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
                'class' => TestEnum::class,
                'expectedResult' => false
            ],
            'protected property exists' => [
                'name' => 'locale',
                'class' => TestEnum::class,
                'expectedResult' => true
            ],
            'private property exists' => [
                'name' => 'priority',
                'class' => TestEnum::class,
                'expectedResult' => false
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
        $extendedProperties = EntityPropertyInfo::getExtendedProperties(TestEnum::class);

        self::assertSame($extendedProperties, []);
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
}
