<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

/**
 * Provides runtime configuration for LiipImagine image filters based on the given context.
 * Implementations can add dynamic settings such as format, quality, metadata preservation, etc.
 */
interface RuntimeConfigProviderInterface
{
    /**
     * Checks if this provider supports configuration for the given filter.
     */
    public function isSupported(string $filter): bool;

    /**
     * Returns runtime configuration array for the filter based on the provided context.
     */
    public function getRuntimeConfig(string $filter, RuntimeContext $context): array;
}
