<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

/**
 * Immutable value object representing a single resolved property or method access found in a Twig template.
 */
final readonly class TemplateAccessEntry
{
    public const string ACCESS_TYPE_PROPERTY = 'property';

    public const string ACCESS_TYPE_METHOD = 'method';

    public function __construct(
        public string $className,
        public string $variableName,
        public string $attributeName,
        public string $accessType,
        public int $lineNumber,
    ) {
    }
}
