<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider arrayProvider
     */
    public function testIsArray($dataType, $expected)
    {
        self::assertSame($expected, DataType::isArray($dataType));
    }

    public function arrayProvider()
    {
        return [
            ['array', true],
            ['objects', true],
            ['object[]', true],
            ['strings', true],
            ['string[]', true],
            ['scalar', false],
            ['object', false],
            ['nestedObject', false],
            ['string', false],
            ['string[]t', false],
            ['[]string', false],
            [null, false],
            ['', false]
        ];
    }

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
            ['', false]
        ];
    }

    /**
     * @dataProvider nestedAssociationProvider
     */
    public function testIsNestedAssociation($dataType, $expected)
    {
        self::assertSame($expected, DataType::isNestedAssociation($dataType));
    }

    public function nestedAssociationProvider()
    {
        return [
            ['nestedAssociation', true],
            ['string', false],
            [null, false],
            ['', false]
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
            ['scalar', true],
            ['object', true],
            ['array', true],
            ['objects', true],
            ['object[]', true],
            ['strings', true],
            ['string[]', true],
            ['nestedObject', true],
            ['string', false],
            ['string[]t', false],
            ['[]string', false],
            [null, false],
            ['', false]
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
            ['', false]
        ];
    }

    /**
     * @dataProvider parseExtendedAssociationProvider
     */
    public function testParseExtendedAssociation($dataType, $expectedAssociationType, $expectedAssociationKind)
    {
        [$associationType, $associationKind] = DataType::parseExtendedAssociation($dataType);
        self::assertSame($expectedAssociationType, $associationType);
        self::assertSame($expectedAssociationKind, $associationKind);
    }

    public function parseExtendedAssociationProvider()
    {
        return [
            ['association:manyToOne', 'manyToOne', null],
            ['association:manyToOne:kind', 'manyToOne', 'kind']
        ];
    }

    /**
     * @dataProvider invalidExtendedAssociationProvider
     */
    public function testParseInvalidExtendedAssociation($dataType)
    {
        $this->expectException(\InvalidArgumentException::class);
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
            ['']
        ];
    }
}
