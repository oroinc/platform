<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\Basic;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;

/**
 * Applies liip imagine filter to the {@see BinaryInterface} object.
 */
class BasicImagineBinaryFilter implements ImagineBinaryFilterInterface
{
    private FilterManager $filterManager;

    public function __construct(FilterManager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    public function applyFilter(BinaryInterface $binary, string $filter, array $runtimeConfig = []): ?BinaryInterface
    {
        return $this->filterManager->applyFilter($binary, $filter, $runtimeConfig);
    }

    public function apply(BinaryInterface $binary, array $runtimeConfig = []): ?BinaryInterface
    {
        return $this->filterManager->apply($binary, $runtimeConfig);
    }
}
