<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Liip\ImagineBundle\Binary\BinaryInterface;

class Thumbnail
{
    /** @var BinaryInterface */
    protected $binary;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var string */
    protected $filter;

    /**
     * @param BinaryInterface $binary
     * @param int $width
     * @param int $height
     * @param string $filter
     */
    public function __construct(BinaryInterface $binary, $width, $height, $filter)
    {
        $this->binary = $binary;
        $this->width = $width;
        $this->height = $height;
        $this->filter = $filter;
    }

    /**
     * @return BinaryInterface
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
