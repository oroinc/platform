<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\Basic;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;

class BasicImagineBinaryFilter implements ImagineBinaryFilterInterface
{
    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @param FilterManager $filterManager
     */
    public function __construct(FilterManager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilter(BinaryInterface $binary, string $filter): BinaryInterface
    {
        return $this->filterManager->applyFilter(
            $binary,
            $filter
        );
    }
}
