<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;

/**
 * Interface for provider of LiipImagine filter runtime config.
 */
interface FilterRuntimeConfigProviderInterface
{
    public function getRuntimeConfigForFilter(BinaryInterface $binary, string $filterName, string $format = ''): array;
}
