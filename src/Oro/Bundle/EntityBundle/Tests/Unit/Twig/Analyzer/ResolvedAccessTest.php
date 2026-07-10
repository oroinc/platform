<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\ResolvedAccess;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use PHPUnit\Framework\TestCase;

final class ResolvedAccessTest extends TestCase
{
    public function testIsCollectionDefaultsToFalseWhenNotProvided(): void
    {
        $resolved = new ResolvedAccess(
            attributeName: 'items',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            entityClass: 'App\Entity\Order',
        );

        self::assertFalse($resolved->isCollection);
    }

    public function testConstructorStoresIsCollectionTrueWhenExplicitlyProvided(): void
    {
        $resolved = new ResolvedAccess(
            attributeName: 'lineItems',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            entityClass: 'App\Entity\OrderItem',
            isCollection: true,
        );

        self::assertTrue($resolved->isCollection);
    }

    public function testSkipAccessEntryDefaultsToFalseWhenNotProvided(): void
    {
        $resolved = new ResolvedAccess(
            attributeName: 'name',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            entityClass: null,
        );

        self::assertFalse($resolved->skipAccessEntry);
    }

    public function testSkipAccessEntryCanBeSetToTrue(): void
    {
        $resolved = new ResolvedAccess(
            attributeName: 'url',
            accessType: TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            entityClass: null,
            skipAccessEntry: true,
        );

        self::assertTrue($resolved->skipAccessEntry);
    }

    public function testConstructorPreservesAllFivePropertiesWhenAllAreProvided(): void
    {
        $resolved = new ResolvedAccess(
            attributeName: 'orderItems',
            accessType: TemplateAccessEntry::ACCESS_TYPE_METHOD,
            entityClass: 'App\Entity\Order',
            isCollection: true,
            skipAccessEntry: false,
        );

        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $resolved->accessType);
        self::assertSame('App\Entity\Order', $resolved->entityClass);
        self::assertTrue($resolved->isCollection);
        self::assertFalse($resolved->skipAccessEntry);
        self::assertSame('orderItems', $resolved->attributeName);
    }

    /**
     * @dataProvider constructorPropertiesProvider
     */
    public function testConstructorPreservesAllSuppliedValues(
        string $attributeName,
        string $accessType,
        ?string $className,
        bool $isCollection,
        bool $skipAccessEntry
    ): void {
        $resolved = new ResolvedAccess(
            attributeName: $attributeName,
            accessType: $accessType,
            entityClass: $className,
            isCollection: $isCollection,
            skipAccessEntry: $skipAccessEntry,
        );

        self::assertSame($attributeName, $resolved->attributeName);
        self::assertSame($accessType, $resolved->accessType);
        self::assertSame($className, $resolved->entityClass);
        self::assertSame($isCollection, $resolved->isCollection);
        self::assertSame($skipAccessEntry, $resolved->skipAccessEntry);
    }

    public static function constructorPropertiesProvider(): iterable
    {
        yield 'property access with null result class and isCollection false' => [
            'name',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            null,
            false,
            false,
        ];
        yield 'method access with null result class and isCollection false' => [
            'getName',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            null,
            false,
            false,
        ];
        yield 'property access with result class and not a collection' => [
            'owner',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            'App\Entity\User',
            false,
            false,
        ];
        yield 'property access with element class and is a collection' => [
            'lineItems',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            'App\Entity\OrderItem',
            true,
            false,
        ];
        yield 'method access with element class and is a collection' => [
            'getTags',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            'App\Entity\Tag',
            true,
            false,
        ];
        yield 'arbitrary access type with deeply nested class name' => [
            'something',
            'custom_access',
            'Acme\Bundle\FooBundle\Entity\Deeply\Nested\SomeClass',
            false,
            false,
        ];
        yield 'null result class name with explicit collection true' => [
            'items',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            null,
            true,
            false,
        ];
        yield 'result class name with leading backslash' => [
            'product',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            '\App\Entity\Product',
            false,
            false,
        ];
        yield 'skip access entry set to true' => [
            'url',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            null,
            false,
            true,
        ];
        yield 'empty string access type' => [
            'name',
            '',
            null,
            false,
            false,
        ];
    }
}
