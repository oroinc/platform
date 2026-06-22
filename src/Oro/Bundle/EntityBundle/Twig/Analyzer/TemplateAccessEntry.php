<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

/**
 * Immutable value object representing a single resolved property or method access found in a Twig template.
 */
final class TemplateAccessEntry
{
    public const ACCESS_TYPE_PROPERTY = 'property';

    public const ACCESS_TYPE_METHOD = 'method';

    public function __construct(
        public string $className,
        public string $variableName,
        public string $attributeName,
        public string $accessType,
        public int $lineNumber,
    ) {
    }
}
