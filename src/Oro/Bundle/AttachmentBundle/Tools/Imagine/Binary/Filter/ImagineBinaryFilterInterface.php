<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter;

use Liip\ImagineBundle\Binary\BinaryInterface;

interface ImagineBinaryFilterInterface
{
    /**
     * @throws \InvalidArgumentException if could not find a filter
     */
    public function applyFilter(BinaryInterface $binary, string $filter): BinaryInterface;
}
