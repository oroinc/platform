<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Represents cached configuration for the sandboxed TWIG templates renderer.
 */
interface TemplateRendererConfigProviderInterface
{
    public const PROPERTIES         = 'properties';
    public const METHODS            = 'methods';
    public const ACCESSORS          = 'accessors';
    public const DEFAULT_FORMATTERS = 'default_formatters';

    /**
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * @return array
     */
    public function getSystemVariableValues(): array;

    /**
     * @param string $entityClass
     *
     * @return array
     */
    public function getEntityVariableProcessors(string $entityClass): array;

    /**
     * Removes all entries from the cache.
     */
    public function clearCache(): void;
}
