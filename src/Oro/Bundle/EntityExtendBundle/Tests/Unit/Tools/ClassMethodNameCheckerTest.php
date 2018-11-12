<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;

class ClassMethodNameCheckerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity';

    /** @var ClassMethodNameChecker */
    protected $checker;

    protected function setUp()
    {
        $this->checker = new ClassMethodNameChecker();
    }

    /**
     * @dataProvider getMethodsDataProvider
     *
     * @param $property
     * @param $prefixes
     * @param array $result
     */
    public function testGetMethods($property, $prefixes, array $result)
    {
        $this->assertEquals($result, $this->checker->getMethods($property, self::TEST_CLASS_NAME, $prefixes));
    }

    /**
     * @return array
     */
    public function getMethodsDataProvider()
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
