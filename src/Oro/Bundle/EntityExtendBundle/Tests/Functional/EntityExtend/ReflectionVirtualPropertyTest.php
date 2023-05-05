<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ReflectionVirtualPropertyTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->bootKernel();
    }

    public function testCreateExtendedProperty(): void
    {
        $virtualReflProperty = ReflectionVirtualProperty::create('phone');

        self::assertSame($virtualReflProperty::class, ReflectionVirtualProperty::class);
        self::assertSame($virtualReflProperty->getName(), 'phone');
    }

    public function testCreateRealProperty(): void
    {
        $virtualReflProperty = ReflectionVirtualProperty::create('email');

        self::assertSame($virtualReflProperty::class, ReflectionVirtualProperty::class);
        self::assertSame($virtualReflProperty->getName(), 'email');
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(object $object, string $property, mixed $expectedResult): void
    {
        $virtualReflProperty = ReflectionVirtualProperty::create($property);


        self::assertSame($virtualReflProperty->getValue($object), $expectedResult);
    }

    public function getValueDataProvider(): array
    {
        $user = new User();
        $user->setUsername('TestName');

        return [
            'real public property' => [
                'object' => $user,
                'name' => 'username',
                'expectedResult' => 'TestName'
            ],
            'extended property' => [
                'object' => $user,
                'name' => 'phone',
                'expectedResult' => null
            ],
            'serialized_data property' => [
                'object' => new AttributeFamily(),
                'name' => 'serialized_data',
                'expectedResult' => null
            ],
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue(object $object, string $property, mixed $value, mixed $expectedResult): void
    {
        $virtualReflProperty = ReflectionVirtualProperty::create($property);
        $virtualReflProperty->setValue($object, $value);

        self::assertSame($object->$property, $expectedResult);
    }

    public function setValueDataProvider(): array
    {
        return [
            'real public property' => [
                'object' => new User(),
                'name' => 'username',
                'value' => 'TestUserName',
                'expectedResult' => null
            ],
            'extended property' => [
                'object' => new User(),
                'name' => 'phone',
                'value' => '+9999999999999',
                'expectedResult' => '+9999999999999'
            ],
            'serialized_data property' => [
                'object' => new AttributeFamily(),
                'name' => 'serialized_data',
                'value' => ['TestData'],
                'expectedResult' => ['TestData']
            ],
        ];
    }
}
