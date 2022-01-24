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
    public function testIsArray(?string $dataType, bool $expected)
    {
        self::assertSame($expected, DataType::isArray($dataType));
    }

    public function arrayProvider(): array
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
    public function testIsNestedObject(?string $dataType, bool $expected)
    {
        self::assertSame($expected, DataType::isNestedObject($dataType));
    }

    public function nestedObjectProvider(): array
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
    public function testIsNestedAssociation(?string $dataType, bool $expected)
    {
        self::assertSame($expected, DataType::isNestedAssociation($dataType));
    }

    public function nestedAssociationProvider(): array
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
    public function testIsAssociationAsField(?string $dataType, bool $expected)
    {
        self::assertSame($expected, DataType::isAssociationAsField($dataType));
    }

    public function associationAsFieldProvider(): array
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
    public function testIsExtendedAssociation(?string $dataType, bool $expected)
    {
        self::assertSame($expected, DataType::isExtendedAssociation($dataType));
    }

    public function extendedAssociationProvider(): array
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
    public function testParseExtendedAssociation(
        string $dataType,
        string $expectedAssociationType,
        ?string $expectedAssociationKind
    ) {
        [$associationType, $associationKind] = DataType::parseExtendedAssociation($dataType);
        self::assertSame($expectedAssociationType, $associationType);
        self::assertSame($expectedAssociationKind, $associationKind);
    }

    public function parseExtendedAssociationProvider(): array
    {
        return [
            ['association:manyToOne', 'manyToOne', null],
            ['association:manyToOne:kind', 'manyToOne', 'kind']
        ];
    }

    /**
     * @dataProvider invalidExtendedAssociationProvider
     */
    public function testParseInvalidExtendedAssociation(string $dataType)
    {
        $this->expectException(\InvalidArgumentException::class);
        DataType::parseExtendedAssociation($dataType);
    }

    public function invalidExtendedAssociationProvider(): array
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
