<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getReverseRelationTypeProvider
     */
    public function testGetReverseRelationType($type, $expectedType)
    {
        $this->assertEquals(
            $expectedType,
            ExtendHelper::getReverseRelationType($type)
        );
    }

    public static function getReverseRelationTypeProvider()
    {
        return [
            ['oneToMany', 'manyToOne'],
            ['manyToOne', 'oneToMany'],
            ['manyToMany', 'manyToMany'],
            ['other', 'other'],
        ];
    }

    /**
     * @dataProvider buildAssociationNameProvider
     */
    public function testBuildAssociationName($targetEntityClassName, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::buildAssociationName($targetEntityClassName)
        );
    }

    public static function buildAssociationNameProvider()
    {
        return [
            ['Oro\Bundle\TestBundle\Entity\Test', 'test_d2f667e'],
            ['Oro\Bundle\TestBundle\Entity\OtherTest', 'other_test_f1fe376e'],
            ['Acme\Bundle\TestBundle\Entity\Test', 'test_77981b51'],
            ['Acme\Bundle\TestBundle\Entity\OtherTest', 'other_test_3efb8e13'],
            ['Test', 'test_784dd132'],
            ['OtherTest', 'other_test_f54366f8'],
        ];
    }

    public function testBuildRelationKey()
    {
        $this->assertEquals(
            'manyToOne|Test\Entity|Test\TargetEntity|testField',
            ExtendHelper::buildRelationKey('Test\Entity', 'testField', 'manyToOne', 'Test\TargetEntity')
        );
    }

    /**
     * @dataProvider isCustomEntityProvider
     */
    public function testIsCustomEntity($className, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::isCustomEntity($className)
        );
    }

    public static function isCustomEntityProvider()
    {
        return [
            ['Extend\Entity\Test', true],
            ['Acme\Bundle\TestBundle\Entity\Test', false],
        ];
    }

    /**
     * @dataProvider getShortClassNameProvider
     */
    public function testGetShortClassName($className, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::getShortClassName($className)
        );
    }

    public static function getShortClassNameProvider()
    {
        return [
            ['Oro\Bundle\TestBundle\Entity\Test', 'Test'],
            ['Acme\Bundle\TestBundle\Entity\Test', 'Test'],
            ['Test', 'Test'],
        ];
    }
}
