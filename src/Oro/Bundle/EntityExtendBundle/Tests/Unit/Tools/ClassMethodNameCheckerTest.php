<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;

class ClassMethodNameCheckerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity';

    /**
     * @dataProvider hasGettersDataProvider
     */
    public function testHasGetters($field, $hasResult)
    {
        $methodNameChecker = new ClassMethodNameChecker();

        $result = $methodNameChecker->hasGetters(self::CLASS_NAME, $field);

        if (!$hasResult) {
            self::assertEquals(0, strlen($result));
        } else {
            self::assertGreaterThan(0, strlen($result));
        }
    }

    /**
     * @return array
     */
    public function hasGettersDataProvider()
    {
        return [
            ['name', true],
            ['email', false],
            ['complex', true],
        ];
    }

    /**
     * @dataProvider hasSettersDataProvider
     */
    public function testHasSetters($field, $hasResult)
    {
        $methodNameChecker = new ClassMethodNameChecker();

        $result = $methodNameChecker->hasSetters(self::CLASS_NAME, $field);

        if (!$hasResult) {
            self::assertEquals(0, strlen($result));
        } else {
            self::assertGreaterThan(0, strlen($result));
        }
    }

    /**
     * @return array
     */
    public function hasSettersDataProvider()
    {
        return [
            ['name', true],
            ['email', false],
            ['complex', false],
            ['someOne', true],
        ];
    }

    /**
     * @dataProvider hasRelationMethodsDataProvider
     */
    public function testTasRelationMethods($field, $hasResult)
    {
        $methodNameChecker = new ClassMethodNameChecker();

        $result = $methodNameChecker->hasRelationMethods(self::CLASS_NAME, $field);

        if (!$hasResult) {
            self::assertEquals(0, strlen($result));
        } else {
            self::assertGreaterThan(0, strlen($result));
        }
    }

    /**
     * @return array
     */
    public function hasRelationMethodsDataProvider()
    {
        return [
            ['name', false],
            ['email', false],
            ['complex', false],
            ['someOne', true],
        ];
    }
}
