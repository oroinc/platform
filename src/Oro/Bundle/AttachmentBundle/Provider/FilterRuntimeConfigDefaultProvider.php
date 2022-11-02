<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Default LiipImagine filter runtime config provider.
 */
class FilterRuntimeConfigDefaultProvider implements FilterRuntimeConfigProviderInterface
{
    public function getRuntimeConfigForFilter(string $filterName, string $format = ''): array
    {
        if ($format) {
            return ['format' => strtolower($format)];
        }

        return [];
    }
}
