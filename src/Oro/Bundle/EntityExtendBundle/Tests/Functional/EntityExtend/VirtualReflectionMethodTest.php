<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class VirtualReflectionMethodTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->bootKernel();
    }

    public function testCreateExtendedMethod(): void
    {
        $virtualReflMethod = VirtualReflectionMethod::create(User::class, 'getPhone');

        self::assertSame($virtualReflMethod::class, VirtualReflectionMethod::class);
        self::assertSame($virtualReflMethod->getName(), 'getPhone');
    }

    public function testGetName(): void
    {
        $virtualReflMethod = VirtualReflectionMethod::create(User::class, 'getUsername');
        // Real property should return DONOR method name 'get'
        self::assertSame($virtualReflMethod->getName(), 'getUsername');

        $virtualReflMethod = VirtualReflectionMethod::create(AttributeFamily::class, 'getImage');
        self::assertSame($virtualReflMethod->getName(), 'getImage');
    }

    /**
     * @dataProvider isPublicDataProvider
     */
    public function testIsPublic(string|object $class, string $method, bool $expectedResult): void
    {
        $virtualReflMethod = VirtualReflectionMethod::create($class, $method);

        self::assertSame($virtualReflMethod->isPublic(), $expectedResult);
    }

    public function isPublicDataProvider(): array
    {
        return [
            'real public method' => [
                'class' => User::class,
                'name' => 'getUserName',
                'expectedResult' => true
            ],
            'undefined method' => [
                'class' => User::class,
                'name' => 'getUndefinedMethod',
                'expectedResult' => true
            ],
            'extended method' => [
                'class' => User::class,
                'name' => 'getPhone',
                'expectedResult' => true
            ],
            'serialized_data method' => [
                'class' => AttributeFamily::class,
                'name' => 'getSerializedData',
                'expectedResult' => true
            ],
        ];
    }

    public function testCreateRealMethod(): void
    {
        $virtualReflMethod = VirtualReflectionMethod::create(User::class, 'getEmail');

        self::assertSame($virtualReflMethod::class, VirtualReflectionMethod::class);
        self::assertSame($virtualReflMethod->getName(), 'getEmail');
    }

    public function testCreateWithoutMethod(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Virtual reflection method is not implemented');

        $reflMethod = new VirtualReflectionMethod(User::class, null);
    }

    /**
     * @dataProvider getNumberOfRequiredParametersDataProvider
     */
    public function testGetNumberOfRequiredParameters(string|object $class, string $method, mixed $expectedResult): void
    {
        $virtualReflMethod = VirtualReflectionMethod::create($class, $method);

        self::assertSame($virtualReflMethod->getNumberOfRequiredParameters(), $expectedResult);
    }

    public function getNumberOfRequiredParametersDataProvider(): array
    {
        return [
            'real public method' => [
                'class' => User::class,
                'name' => 'setUsername',
                'expectedResult' => 1
            ],
            'undefined method' => [
                'class' => new User(),
                'name' => 'getUndefinedMethod',
                'expectedResult' => 0
            ],
            'extended method set' => [
                'class' => User::class,
                'name' => 'setPhone',
                'expectedResult' => 1
            ],
            'extended method get' => [
                'class' => User::class,
                'name' => 'getPhone',
                'expectedResult' => 0
            ],
            'serialized_data method' => [
                'class' => AttributeFamily::class,
                'name' => 'getSerializedData',
                'expectedResult' => 0
            ],
        ];
    }

    public function testInvoke(): void
    {
        $user = new User();
        $virtualReflMethod = VirtualReflectionMethod::create($user, 'setPhone');
        $argument1 = '+999999999999999';
        $virtualReflMethod->invoke($user, $argument1);

        self::assertSame($argument1, $user->getPhone());

        $userEmail = new AttributeFamily();
        $virtualReflMethod = VirtualReflectionMethod::create($userEmail, 'setEntityClass');
        $argument1 = AttributeFamily::class;
        $virtualReflMethod->invokeArgs($userEmail, [$argument1]);

        self::assertSame($argument1, $userEmail->getEntityClass());
    }
}
