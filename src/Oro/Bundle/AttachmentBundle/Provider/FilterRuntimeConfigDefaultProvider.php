<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;

/**
 * Default LiipImagine filter runtime config provider.
 */
class FilterRuntimeConfigDefaultProvider implements FilterRuntimeConfigProviderInterface
{
    public function getRuntimeConfigForFilter(BinaryInterface $binary, string $filterName, string $format = ''): array
    {
        return [];
    }
}
