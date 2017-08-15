<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter;

use Liip\ImagineBundle\Binary\BinaryInterface;

interface ImagineBinaryFilterInterface
{
    /**
     * @param BinaryInterface $binary
     * @param string          $filter
     *
     * @throws \InvalidArgumentException if could not find a filter
     *
     * @return BinaryInterface
     */
    public function applyFilter(BinaryInterface $binary, string $filter): BinaryInterface;
}
