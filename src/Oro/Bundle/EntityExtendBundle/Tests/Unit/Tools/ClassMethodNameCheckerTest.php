<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;

class ClassMethodNameCheckerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS_NAME = TestEntity::class;

    /** @var ClassMethodNameChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new ClassMethodNameChecker();
    }

    /**
     * @dataProvider getMethodsDataProvider
     */
    public function testGetMethods($property, $prefixes, array $result)
    {
        $this->assertEquals($result, $this->checker->getMethods($property, self::TEST_CLASS_NAME, $prefixes));
    }

    public function getMethodsDataProvider(): array
    {
        return [
            'two methods' => ['name', ClassMethodNameChecker::$setters, ['setName']],
            'one setter' => ['name', ['set'], ['setName']],
            'getters' => ['name', ClassMethodNameChecker::$getters, ['getName', 'isName']],
            'relations methods' => ['name', ClassMethodNameChecker::$relationMethods, ['setDefaultName', 'addName']],
            'empty' => [
                'newProperty',
                array_merge(
                    ClassMethodNameChecker::$getters,
                    ClassMethodNameChecker::$setters,
                    ClassMethodNameChecker::$relationMethods
                ),
                []
            ]
        ];
    }
}
