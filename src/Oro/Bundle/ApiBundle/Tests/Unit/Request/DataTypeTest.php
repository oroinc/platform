<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\DataType;

class DataTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nestedObjectProvider
     */
    public function testIsNestedObject($dataType, $expected)
    {
        self::assertSame($expected, DataType::isNestedObject($dataType));
    }

    public function nestedObjectProvider()
    {
        return [
            ['nestedObject', true],
            ['object', false],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider associationAsFieldProvider
     */
    public function testIsAssociationAsField($dataType, $expected)
    {
        self::assertSame($expected, DataType::isAssociationAsField($dataType));
    }

    public function associationAsFieldProvider()
    {
        return [
            ['array', true],
            ['object', true],
            ['scalar', true],
            ['nestedObject', true],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider extendedAssociationProvider
     */
    public function testIsExtendedAssociation($dataType, $expected)
    {
        self::assertSame($expected, DataType::isExtendedAssociation($dataType));
    }

    public function extendedAssociationProvider()
    {
        return [
            ['association:manyToOne', true],
            ['association:manyToOne:kind', true],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider parseExtendedAssociationProvider
     */
    public function testParseExtendedAssociation($dataType, $expectedAssociationType, $expectedAssociationKind)
    {
        list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
        self::assertSame($expectedAssociationType, $associationType);
        self::assertSame($expectedAssociationKind, $associationKind);
    }

    public function parseExtendedAssociationProvider()
    {
        return [
            ['association:manyToOne', 'manyToOne', null],
            ['association:manyToOne:kind', 'manyToOne', 'kind'],
        ];
    }

    /**
     * @dataProvider invalidExtendedAssociationProvider
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalidExtendedAssociation($dataType)
    {
        DataType::parseExtendedAssociation($dataType);
    }

    public function invalidExtendedAssociationProvider()
    {
        return [
            ['string'],
            ['association'],
            ['association:'],
            ['association::'],
            ['association:manyToOne:'],
            [null],
            [''],
        ];
    }

    /**
     * @dataProvider extendedInverseAssociationProvider
     */
    public function testIsExtendedInverseAssociation($dataType, $expected)
    {
        self::assertSame($expected, DataType::isExtendedInverseAssociation($dataType));
    }

    public function extendedInverseAssociationProvider()
    {
        return [
            ['inverseAssociation:manyToOne:/Acme/Demo/Entity', true],
            ['inverseAssociation:manyToOne:/Acme/Demo/Entity:manyToOne', true],
            ['inverseAssociation:manyToOne:/Acme/Demo/Entity:manyToOne:kind', true],
            ['association:manyToOne:kind', false],
            ['string', false],
            [null, false],
            ['', false],
        ];
    }

    /**
     * @dataProvider parseExtendedInverseAssociationProvider
     */
    public function testParseExtendedInverseAssociation(
        $dataType,
        $expectedSourceClass,
        $expectedAssociationType,
        $expectedAssociationKind
    ) {
        list($associationClass, $associationType, $associationKind) =
            DataType::parseExtendedInverseAssociation($dataType);
        self::assertSame($expectedSourceClass, $associationClass);
        self::assertSame($expectedAssociationType, $associationType);
        self::assertSame($expectedAssociationKind, $associationKind);
    }

    public function parseExtendedInverseAssociationProvider()
    {
        return [
            [
                'inverseAssociation:Acme/DemoBundle/Entity/TestEntity:manyToOne',
                'Acme/DemoBundle/Entity/TestEntity',
                'manyToOne',
                null
            ],
            [
                'inverseAssociation:Acme/DemoBundle/Entity/TestEntity:manyToOne:kind',
                'Acme/DemoBundle/Entity/TestEntity',
                'manyToOne',
                'kind'
            ],
        ];
    }

    /**
     * @dataProvider invalidExtendedAssociationProvider
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalidExtendedInverseAssociation($dataType)
    {
        DataType::parseExtendedInverseAssociation($dataType);
    }

    public function invalidExtendedInverseAssociationProvider()
    {
        return [
            ['string'],
            ['inverseAssociation'],
            ['inverseAssociation:'],
            ['inverseAssociation::'],
            ['inverseAssociation:/Acme/Demo:'],
            ['inverseAssociation:/Acme/Demo:manyToOne:'],
            [null],
            [''],
        ];
    }

    /**
     * @dataProvider buildExtendedInverseAssociationProvider
     */
    public function testBuildExtendedInverseAssociation($source, $type, $kind, $result)
    {
        $this->assertEquals($result, DataType::buildExtendedInverseAssociation($source, $type, $kind));
    }

    public function buildExtendedInverseAssociationProvider()
    {
        return [
            ['Source\Class', 'manyToOne', null, 'inverseAssociation:Source\Class:manyToOne'],
            ['Source\Class', 'manyToOne', 'someKind', 'inverseAssociation:Source\Class:manyToOne:someKind']
        ];
    }
}
