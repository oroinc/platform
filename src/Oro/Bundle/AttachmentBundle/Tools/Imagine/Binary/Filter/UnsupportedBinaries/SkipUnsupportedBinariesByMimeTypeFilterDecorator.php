<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;

class SkipUnsupportedBinariesByMimeTypeFilterDecorator implements ImagineBinaryFilterInterface
{
    /**
     * @var ImagineBinaryFilterInterface
     */
    private $decoratedFilter;

    /**
     * @var array
     */
    private $unsupportedMimeTypes;

    /**
     * @param ImagineBinaryFilterInterface $decoratedFilter
     * @param array                        $unsupportedMimeTypes
     */
    public function __construct(ImagineBinaryFilterInterface $decoratedFilter, array $unsupportedMimeTypes)
    {
        $this->decoratedFilter = $decoratedFilter;
        $this->unsupportedMimeTypes = $unsupportedMimeTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilter(BinaryInterface $binary, string $filter): BinaryInterface
    {
        $mimeType = $binary->getMimeType();

        if (in_array($mimeType, $this->unsupportedMimeTypes, true)) {
            return $binary;
        }

        return $this->decoratedFilter->applyFilter($binary, $filter);
    }
}
