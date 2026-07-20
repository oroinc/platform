<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityBundle\Twig\Analyzer\ResolvedAccess;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TypeResolverInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Test stub that implements both TypeResolverInterface and ResetInterface,
 * allowing tests to verify that ChainTypeResolver calls reset() on resettable resolvers.
 */
class ResettableTypeResolverStub implements TypeResolverInterface, ResetInterface
{
    private int $resetCallCount = 0;

    public function __construct(
        private readonly ?ResolvedAccess $resolvedAccess = null,
    ) {
    }

    #[\Override]
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess
    {
        return $this->resolvedAccess;
    }

    #[\Override]
    public function reset(): void
    {
        $this->resetCallCount++;
    }

    public function getResetCallCount(): int
    {
        return $this->resetCallCount;
    }
}
