<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

/**
 * Internal value object returned by {@see TypeResolverInterface::resolve()} representing the result
 * of resolving a class attribute access.
 *
 * `$attributeName` holds the canonical attribute name that the resolver matched — after any camelCase ↔ snake_case
 * normalisation performed by the resolver. For example, when `getPasswordExpiresAt` resolves via the
 * snake_case property `$password_expires_at`, `attributeName` is `password_expires_at`. This lets consumers
 * know the actual name used without having to repeat the normalisation logic themselves.
 *
 * When `$isCollection` is true, `$className` represents the element type of the collection
 * (not the collection class itself). This allows ForNode processing to correctly resolve
 * the type of each iteration variable.
 *
 * When `$skipAccessEntry` is true, the {@see AccessNodeVisitor} must NOT record an access entry for this step.
 * This is used for virtual variable namespace prefixes (e.g., resolving `url` when `url.view` is a known virtual
 * variable) to prevent false positives in the template security policy checker.
 */
final readonly class ResolvedAccess
{
    public function __construct(
        public string $attributeName,
        public string $accessType,
        public ?string $entityClass,
        public bool $isCollection = false,
        public bool $skipAccessEntry = false,
    ) {
    }
}
