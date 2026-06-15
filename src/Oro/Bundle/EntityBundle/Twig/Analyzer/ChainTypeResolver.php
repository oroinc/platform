<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Symfony\Contracts\Service\ResetInterface;
use Twig\Template;

/**
 * Iterates over registered type resolvers in priority order and returns the first non-null result.
 * Used as the primary TypeResolverInterface implementation in the DI container.
 */
class ChainTypeResolver implements TypeResolverInterface, ResetInterface
{
    /**
     * @param iterable<TypeResolverInterface> $typeResolvers Ordered by priority (highest first)
     */
    public function __construct(
        private readonly iterable $typeResolvers,
    ) {
    }

    #[\Override]
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess
    {
        if (Template::ARRAY_CALL === $twigCallType) {
            return null;
        }

        foreach ($this->typeResolvers as $resolver) {
            $result = $resolver->resolve($className, $attributeName, $twigCallType);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }

    #[\Override]
    public function reset(): void
    {
        foreach ($this->typeResolvers as $resolver) {
            if ($resolver instanceof ResetInterface) {
                $resolver->reset();
            }
        }
    }
}
