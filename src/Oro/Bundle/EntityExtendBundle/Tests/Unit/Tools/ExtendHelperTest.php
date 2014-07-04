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
    public function testBuildAssociationName($targetEntityClassName, $associationKind, $expected)
    {
        $this->assertEquals(
            $expected,
            ExtendHelper::buildAssociationName($targetEntityClassName, $associationKind)
        );
    }

    public static function buildAssociationNameProvider()
    {
        return [
            ['Oro\Bundle\TestBundle\Entity\Test', null, 'test_d2f667e'],
            ['Oro\Bundle\TestBundle\Entity\Test', 'test', 'test_9a6fc24b'],
            ['Oro\Bundle\TestBundle\Entity\OtherTest', null, 'other_test_f1fe376e'],
            ['Oro\Bundle\TestBundle\Entity\OtherTest', 'test', 'other_test_14ac1fd7'],
            ['Acme\Bundle\TestBundle\Entity\Test', null, 'test_77981b51'],
            ['Acme\Bundle\TestBundle\Entity\Test', 'test', 'test_21bc9fd6'],
            ['Acme\Bundle\TestBundle\Entity\OtherTest', null, 'other_test_3efb8e13'],
            ['Acme\Bundle\TestBundle\Entity\OtherTest', 'test', 'other_test_8ca3d713'],
            ['Test', null, 'test_784dd132'],
            ['Test', 'test', 'test_4c5b140f'],
            ['OtherTest', null, 'other_test_f54366f8'],
            ['OtherTest', 'test', 'other_test_4ee028ce'],
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
