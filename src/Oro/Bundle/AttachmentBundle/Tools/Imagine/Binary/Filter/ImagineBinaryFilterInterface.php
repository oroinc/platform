<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter;

use Liip\ImagineBundle\Binary\BinaryInterface;

/**
 * Interface for class that applies liip imagine filter config to the {@see BinaryInterface} object.
 */
interface ImagineBinaryFilterInterface
{
    public function applyFilter(BinaryInterface $binary, string $filter, array $runtimeConfig = []): ?BinaryInterface;

    public function apply(BinaryInterface $binary, array $runtimeConfig): ?BinaryInterface;
}
