<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\NoopResolver;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ResolvedAccess;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use PHPUnit\Framework\TestCase;
use Twig\Template;

final class NoopResolverTest extends TestCase
{
    private NoopResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolver = new NoopResolver();
    }

    public function testResolveReturnsNullForArrayCallType(): void
    {
        $result = $this->resolver->resolve('SomeClass', 'attribute', Template::ARRAY_CALL);

        self::assertNull($result);
    }

    /**
     * @dataProvider nonArrayCallTypeProvider
     */
    public function testResolveReturnsResolvedAccessForNonArrayCallType(
        string $twigCallType,
        string $attributeName,
        string $expectedAccessType
    ): void {
        $result = $this->resolver->resolve('SomeClass', $attributeName, $twigCallType);

        self::assertInstanceOf(ResolvedAccess::class, $result);
        self::assertSame($expectedAccessType, $result->accessType);
        self::assertSame($attributeName, $result->attributeName);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertFalse($result->skipAccessEntry);
    }

    public static function nonArrayCallTypeProvider(): iterable
    {
        yield 'method call maps to method access type' => [
            Template::METHOD_CALL,
            'getName',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
        ];
        yield 'any call maps to property access type' => [
            Template::ANY_CALL,
            'price',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
        ];
    }
}
