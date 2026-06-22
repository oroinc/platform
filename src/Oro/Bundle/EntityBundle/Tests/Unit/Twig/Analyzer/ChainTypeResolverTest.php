<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Tests\Unit\Stub\ResettableTypeResolverStub;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ChainTypeResolver;
use Oro\Bundle\EntityBundle\Twig\Analyzer\ResolvedAccess;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TypeResolverInterface;
use PHPUnit\Framework\TestCase;
use Twig\Template;

final class ChainTypeResolverTest extends TestCase
{
    public function testResolveReturnsNullImmediatelyForArrayCallType(): void
    {
        $resolver = $this->createMock(TypeResolverInterface::class);
        $resolver
            ->expects(self::never())
            ->method('resolve');

        $chain = new ChainTypeResolver([$resolver]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ARRAY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenNoResolversAreRegistered(): void
    {
        $chain = new ChainTypeResolver([]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenAllResolversReturnNull(): void
    {
        $firstResolver = $this->createMock(TypeResolverInterface::class);
        $firstResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn(null);

        $secondResolver = $this->createMock(TypeResolverInterface::class);
        $secondResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn(null);

        $chain = new ChainTypeResolver([$firstResolver, $secondResolver]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsResultFromFirstResolver(): void
    {
        $expectedResult = new ResolvedAccess(attributeName: '', accessType: 'property', entityClass: null);

        $resolver = $this->createMock(TypeResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn($expectedResult);

        $chain = new ChainTypeResolver([$resolver]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertSame($expectedResult, $result);
    }

    public function testResolveSkipsNullReturningResolverAndReturnsNextMatch(): void
    {
        $expectedResult = new ResolvedAccess(attributeName: '', accessType: 'method', entityClass: 'Acme\\OtherClass');

        $firstResolver = $this->createMock(TypeResolverInterface::class);
        $firstResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn(null);

        $secondResolver = $this->createMock(TypeResolverInterface::class);
        $secondResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn($expectedResult);

        $chain = new ChainTypeResolver([$firstResolver, $secondResolver]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertSame($expectedResult, $result);
    }

    public function testResolveDoesNotCallSubsequentResolversAfterFirstMatchIsFound(): void
    {
        $expectedResult = new ResolvedAccess(attributeName: '', accessType: 'property', entityClass: null);

        $firstResolver = $this->createMock(TypeResolverInterface::class);
        $firstResolver
            ->expects(self::once())
            ->method('resolve')
            ->willReturn($expectedResult);

        $secondResolver = $this->createMock(TypeResolverInterface::class);
        $secondResolver
            ->expects(self::never())
            ->method('resolve');

        $chain = new ChainTypeResolver([$firstResolver, $secondResolver]);

        $result = $chain->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertSame($expectedResult, $result);
    }

    public function testResolvePassesCorrectArgumentsToResolver(): void
    {
        $className = 'Acme\\Entity\\Product';
        $attributeName = 'priceList';
        $callType = Template::ANY_CALL;
        $expectedResult = new ResolvedAccess(
            attributeName: 'priceList',
            accessType: 'method',
            entityClass: 'Acme\\Entity\\PriceList'
        );

        $resolver = $this->createMock(TypeResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with($className, $attributeName, $callType)
            ->willReturn($expectedResult);

        $chain = new ChainTypeResolver([$resolver]);

        $result = $chain->resolve($className, $attributeName, $callType);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider nonArrayCallTypeProvider
     */
    public function testResolveWithNonArrayCallTypeDelegatesToResolvers(string $callType): void
    {
        $expectedResult = new ResolvedAccess(attributeName: '', accessType: 'property', entityClass: null);

        $resolver = $this->createMock(TypeResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with(self::anything(), self::anything(), $callType)
            ->willReturn($expectedResult);

        $chain = new ChainTypeResolver([$resolver]);

        $result = $chain->resolve('SomeClass', 'attribute', $callType);

        self::assertSame($expectedResult, $result);
    }

    public static function nonArrayCallTypeProvider(): iterable
    {
        yield 'method call type' => [Template::METHOD_CALL];
    }

    public function testResetDoesNothingWhenNoResolversAreRegistered(): void
    {
        $chain = new ChainTypeResolver([]);
        $chain->reset();
        $this->addToAssertionCount(1);
    }

    public function testResetCallsResetOnResolverThatImplementsResetInterface(): void
    {
        $resettableResolver = new ResettableTypeResolverStub();

        $chain = new ChainTypeResolver([$resettableResolver]);
        $chain->reset();

        self::assertSame(1, $resettableResolver->getResetCallCount());
    }

    public function testResetCallsResetOnAllResettableResolversAndSkipsNonResettable(): void
    {
        $firstResettable = new ResettableTypeResolverStub();
        $nonResettable = $this->createMock(TypeResolverInterface::class);
        $secondResettable = new ResettableTypeResolverStub();

        $chain = new ChainTypeResolver([$firstResettable, $nonResettable, $secondResettable]);
        $chain->reset();

        self::assertSame(1, $firstResettable->getResetCallCount());
        self::assertSame(1, $secondResettable->getResetCallCount());
    }
}
