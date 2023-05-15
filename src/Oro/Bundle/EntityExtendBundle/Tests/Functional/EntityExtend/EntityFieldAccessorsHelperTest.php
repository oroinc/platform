<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldAccessorsHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityFieldAccessorsHelperTest extends WebTestCase
{
    public function setUp(): void
    {
        $this->bootKernel();
    }

    public function testGetInflector(): void
    {
        $inflector = EntityFieldAccessorsHelper::getInflector();

        self::assertSame($inflector::class, Inflector::class);
    }

    /**
     * @dataProvider getterNameDataProvider
     */
    public function testGetterName(string $property, mixed $expectedResult): void
    {
        $getterName = EntityFieldAccessorsHelper::getterName($property);

        self::assertSame($expectedResult, $getterName);
    }

    public function getterNameDataProvider(): array
    {
        return [
            'lowercase property' => [
                'name' => 'username',
                'expectedResult' => 'getUsername'
            ],
            'snake case property' => [
                'name' => 'user_name',
                'expectedResult' => 'getUserName'
            ],
            'camelize property' => [
                'name' => 'userName',
                'expectedResult' => 'getUserName'
            ],
            'first camelize property ' => [
                'name' => 'UserName',
                'expectedResult' => 'getUserName'
            ],
            'free camelize property ' => [
                'name' => 'UserNamE',
                'expectedResult' => 'getUserNamE'
            ],
        ];
    }

    /**
     * @dataProvider setterNameDataProvider
     */
    public function testSetterName(string $property, mixed $expectedResult): void
    {
        $getterName = EntityFieldAccessorsHelper::setterName($property);

        self::assertSame($expectedResult, $getterName);
    }

    public function setterNameDataProvider(): array
    {
        return [
            'lowercase property' => [
                'name' => 'lastname',
                'expectedResult' => 'setLastname'
            ],
            'snake case property' => [
                'name' => 'user_name',
                'expectedResult' => 'setUserName'
            ],
            'camelize property' => [
                'name' => 'lastName',
                'expectedResult' => 'setLastName'
            ],
            'first camelize property ' => [
                'name' => 'LastName',
                'expectedResult' => 'setLastName'
            ],
            'free camelize property ' => [
                'name' => 'LastNaMe',
                'expectedResult' => 'setLastNaMe'
            ],
        ];
    }

    /**
     * @dataProvider adderNameDataProvider
     */
    public function testAdderName(string $property, mixed $expectedResult): void
    {
        $getterName = EntityFieldAccessorsHelper::adderName($property);

        self::assertSame($expectedResult, $getterName);
    }

    public function adderNameDataProvider(): array
    {
        return [
            'lowercase property' => [
                'name' => 'username',
                'expectedResult' => 'addUsername'
            ],
            'snake case property' => [
                'name' => 'user_name',
                'expectedResult' => 'addUserName'
            ],
            'camelize property' => [
                'name' => 'userName',
                'expectedResult' => 'addUserName'
            ],
            'first camelize property ' => [
                'name' => 'UserName',
                'expectedResult' => 'addUserName'
            ],
            'free camelize property ' => [
                'name' => 'UserNamE',
                'expectedResult' => 'addUserNamE'
            ],
        ];
    }

    /**
     * @dataProvider removeNameDataProvider
     */
    public function testRemoveName(string $property, mixed $expectedResult): void
    {
        $getterName = EntityFieldAccessorsHelper::removerName($property);

        self::assertSame($expectedResult, $getterName);
    }

    public function removeNameDataProvider(): array
    {
        return [
            'lowercase property' => [
                'name' => 'firstname',
                'expectedResult' => 'removeFirstname'
            ],
            'snake case property' => [
                'name' => 'user_name',
                'expectedResult' => 'removeUserName'
            ],
            'camelize property' => [
                'name' => 'firstName',
                'expectedResult' => 'removeFirstName'
            ],
            'first camelize property ' => [
                'name' => 'FirstName',
                'expectedResult' => 'removeFirstName'
            ],
            'free camelize property ' => [
                'name' => 'FirstNaMe',
                'expectedResult' => 'removeFirstNaMe'
            ],
        ];
    }
}
