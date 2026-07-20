<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use PHPUnit\Framework\TestCase;

final class TemplateAccessEntryTest extends TestCase
{
    public function testAccessTypePropertyConstantHasExpectedValue(): void
    {
        self::assertSame('property', TemplateAccessEntry::ACCESS_TYPE_PROPERTY);
    }

    public function testAccessTypeMethodConstantHasExpectedValue(): void
    {
        self::assertSame('method', TemplateAccessEntry::ACCESS_TYPE_METHOD);
    }

    /**
     * @dataProvider constructorPropertiesProvider
     */
    public function testConstructorPreservesAllSuppliedValues(
        string $className,
        string $variableName,
        string $attributeName,
        string $accessType,
        int $lineNumber
    ): void {
        $entry = new TemplateAccessEntry($className, $variableName, $attributeName, $accessType, $lineNumber);

        self::assertSame($className, $entry->className);
        self::assertSame($variableName, $entry->variableName);
        self::assertSame($attributeName, $entry->attributeName);
        self::assertSame($accessType, $entry->accessType);
        self::assertSame($lineNumber, $entry->lineNumber);
    }

    public static function constructorPropertiesProvider(): iterable
    {
        yield 'property access on first line' => [
            'App\Entity\User',
            'user',
            'username',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        ];
        yield 'method access on deep line number' => [
            'App\Entity\Order',
            'order',
            'getLineItems',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            100,
        ];
        yield 'fully qualified class name with leading backslash' => [
            '\App\Entity\Product',
            'product',
            'price',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            7,
        ];
        yield 'deeply nested namespace class' => [
            'Acme\Bundle\FooBundle\Entity\Deeply\Nested\SomeClass',
            'item',
            'getValue',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            42,
        ];
        yield 'variable and attribute with underscores' => [
            'App\Entity\LineItem',
            'line_item',
            'unit_price',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            3,
        ];
        yield 'zero line number' => [
            'App\Entity\Product',
            'product',
            'name',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            0,
        ];
        yield 'large line number' => [
            'App\Entity\Product',
            'product',
            'sku',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            9999,
        ];
        yield 'empty class name' => [
            '',
            'variable',
            'attribute',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        ];
        yield 'empty variable name' => [
            'App\Entity\User',
            '',
            'attribute',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        ];
        yield 'empty attribute name' => [
            'App\Entity\User',
            'user',
            '',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        ];
    }
}
