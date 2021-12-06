<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;

/**
 * Prevents applying a filter for {@see BinaryInterface} objects with unsupported mime types.
 */
class SkipUnsupportedBinariesByMimeTypeFilterDecorator implements ImagineBinaryFilterInterface
{
    private ImagineBinaryFilterInterface $decoratedFilter;

    private array $unsupportedMimeTypes;

    public function __construct(ImagineBinaryFilterInterface $decoratedFilter, array $unsupportedMimeTypes)
    {
        $this->decoratedFilter = $decoratedFilter;
        $this->unsupportedMimeTypes = $unsupportedMimeTypes;
    }

    public function applyFilter(BinaryInterface $binary, string $filter, array $runtimeConfig = []): ?BinaryInterface
    {
        if (!$this->isApplicable($binary)) {
            return $binary;
        }

        return $this->decoratedFilter->applyFilter($binary, $filter, $runtimeConfig);
    }

    public function apply(BinaryInterface $binary, array $runtimeConfig): ?BinaryInterface
    {
        if (!$this->isApplicable($binary)) {
            return $binary;
        }

        return $this->decoratedFilter->apply($binary, $runtimeConfig);
    }

    private function isApplicable(BinaryInterface $binary): bool
    {
        return !in_array($binary->getMimeType(), $this->unsupportedMimeTypes, true);
    }
}
