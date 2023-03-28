<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class EntityReflectionClassTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->bootKernel();
    }

    /**
     * @dataProvider hasPropertyDataProvider
     */
    public function testHasProperty(string $class, string $property, bool $exists): void
    {
        $reflClass = new EntityReflectionClass($class);
        self::assertSame($exists, $reflClass->hasProperty($property));
    }

    public function hasPropertyDataProvider(): array
    {
        return [
            'real property exists' => [
                'class' => User::class,
                'property' => 'username',
                'exists' => true
            ],
            'extend field exists' => [
                'class' => User::class,
                'property' => 'phone',
                'exists' => true
            ],
            'attribute field exists' => [
                'class' => User::class,
                'property' => 'avatar',
                'exists' => true
            ],
            'multi enum field exists' => [
                'class' => User::class,
                'property' => 'title',
                'exists' => true
            ],
            'serialized_data field exists' => [
                'class' => User::class,
                'property' => 'serialized_data',
                'exists' => true
            ],
            'undefined property does not exists' => [
                'class' => User::class,
                'property' => 'undefined_property_name',
                'exists' => false
            ],
        ];
    }

    /**
     * @dataProvider getPropertiesDataProvider
     */
    public function testGetProperties(string $class, array $properties): void
    {
        $reflClass = new EntityReflectionClass($class);
        $reflProperties = array_map(
            function ($reflProperty) {
                return $reflProperty->getName() . $reflProperty::class;
            },
            $reflClass->getProperties()
        );
        foreach ($properties as $property => $typeReflection) {
            self::assertTrue(in_array($property . $typeReflection, $reflProperties));
        }
    }

    public function getPropertiesDataProvider(): array
    {
        return [
            'real properties exists' => [
                'class' => User::class,
                'properties' => [
                    'id' => \ReflectionProperty::class,
                    'username' => \ReflectionProperty::class,
                    'email' => \ReflectionProperty::class,
                ]
            ],
            'extend properties exists' => [
                'class' => User::class,
                'properties' => [
                    'title' => ReflectionVirtualProperty::class,
                    'avatar' => ReflectionVirtualProperty::class,
                    'phone' => ReflectionVirtualProperty::class,
                ]
            ],
        ];
    }

    /**
     * @dataProvider getPropertyDataProvider
     */
    public function testGetProperty(string $class, string $property, \ReflectionProperty $reflectionProperty): void
    {
        $reflClass = new EntityReflectionClass($class);
        $realReflectionProperty = $reflClass->getProperty($property);

        self::assertSame($reflectionProperty->getName(), $realReflectionProperty->getName());
        self::assertSame($reflectionProperty::class, $realReflectionProperty::class);
    }

    public function getPropertyDataProvider(): array
    {
        return [
            'real property exists' => [
                'class' => User::class,
                'property' => 'email',
                'reflectionProperty' => new \ReflectionProperty(User::class, 'email')
            ],
            'extend field exists' => [
                'class' => User::class,
                'property' => 'phone',
                'reflectionProperty' => ReflectionVirtualProperty::create('phone')
            ],
        ];
    }

    public function testGetPropertyFailed(): void
    {
        $reflClass = new EntityReflectionClass(User::class);
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Property %s::%s does not exist in extended entity.',
                User::class,
                'undefinedPropertyName'
            )
        );
        $reflClass->getProperty('undefinedPropertyName');
    }

    /**
     * @dataProvider hasMethodDataProvider
     */
    public function testHasMethod(string $class, string $method, bool $exists): void
    {
        $reflClass = new EntityReflectionClass($class);

        self::assertSame($exists, $reflClass->hasMethod($method));
    }

    public function hasMethodDataProvider(): array
    {
        return [
            'method get real property exists' => [
                'class' => User::class,
                'method' => 'getEmail',
                'exists' => true
            ],
            'method set real property exists' => [
                'class' => User::class,
                'method' => 'setEmail',
                'exists' => true
            ],
            'method get extend field exists' => [
                'class' => User::class,
                'method' => 'getPhone',
                'exists' => true
            ],
            'method set extend field exists' => [
                'class' => User::class,
                'method' => 'setPhone',
                'exists' => true
            ],
            'method get serialized filed does not exists' => [
                'class' => User::class,
                'method' => 'getAvatar',
                'exists' => true
            ],
            'method set serialized filed does not exists' => [
                'class' => User::class,
                'method' => 'setAvatar',
                'exists' => true
            ],
            'method get multi enum field exists' => [
                'class' => User::class,
                'method' => 'getTitle',
                'exists' => true
            ],
            'method set multi enum field exists' => [
                'class' => User::class,
                'method' => 'setTitle',
                'exists' => true
            ],
            'get serialized_data method exists' => [
                'class' => User::class,
                'method' => 'getSerializedData',
                'exists' => true
            ],
            'undefined method get does not exists' => [
                'class' => User::class,
                'method' => 'getUndefinedMethod',
                'exists' => false
            ],
        ];
    }

    /**
     * @dataProvider getMethodDataProvider
     */
    public function testGetMethod(string $class, string $method, \ReflectionMethod $reflectionMethod): void
    {
        $reflClass = new EntityReflectionClass($class);
        $realReflectionMethod = $reflClass->getMethod($method);

        self::assertSame($reflectionMethod->getName(), $realReflectionMethod->getName());
        self::assertSame($reflectionMethod::class, $realReflectionMethod::class);
    }

    public function getMethodDataProvider(): array
    {
        return [
            'method get real property exists' => [
                'class' => User::class,
                'method' => 'getEmail',
                'reflectionProperty' => new \ReflectionMethod(User::class, 'getEmail')
            ],
            'method set real property exists' => [
                'class' => User::class,
                'method' => 'setEmail',
                'reflectionProperty' => new \ReflectionMethod(User::class, 'setEmail')
            ],
            'virtual method get extend field exists' => [
                'class' => User::class,
                'method' => 'getTitle',
                'reflectionProperty' => VirtualReflectionMethod::create(User::class, 'getTitle')
            ],
            'virtual method set extend field exists' => [
                'class' => User::class,
                'method' => 'setTitle',
                'reflectionProperty' => VirtualReflectionMethod::create(User::class, 'setTitle')
            ],
        ];
    }

    public function testGetMethodFailed(): void
    {
        $reflClass = new EntityReflectionClass(User::class);
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'method %s::%s does not exist in extended entity.',
                User::class,
                'getUndefinedMethodName'
            )
        );
        $reflClass->getMethod('getUndefinedMethodName');
    }
}
