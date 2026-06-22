<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Twig\Template;

/**
 * NoopResolver is a fallback implementation of TypeResolverInterface for Twig template analysis.
 *
 * It returns null for array access and a generic ResolvedAccess for method/property calls,
 * without performing any actual type resolution. Used when no specific resolver is available.
 */
class NoopResolver implements TypeResolverInterface
{
    #[\Override]
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess
    {
        if ($twigCallType === Template::ARRAY_CALL) {
            return null;
        }

        $accessType = $twigCallType === Template::METHOD_CALL
            ? TemplateAccessEntry::ACCESS_TYPE_METHOD
            : TemplateAccessEntry::ACCESS_TYPE_PROPERTY;

        return new ResolvedAccess(
            attributeName: $attributeName,
            accessType: $accessType,
            entityClass: null,
        );
    }
}
