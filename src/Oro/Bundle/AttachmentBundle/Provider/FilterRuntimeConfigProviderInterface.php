<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Interface for provider of LiipImagine filter runtime config.
 */
interface FilterRuntimeConfigProviderInterface
{
    public function getRuntimeConfigForFilter(string $filterName, string $format = ''): array;
}
