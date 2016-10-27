<?php

namespace Oro\Bundle\ApiBundle\Collection;

class IncludedObjectData
{
    /** @var string */
    private $path;

    /** @var int */
    private $index;

    /** @var bool */
    private $existing;

    /**
     * @param string $path
     * @param int    $index
     * @param bool   $existing
     */
    public function __construct($path, $index, $existing = false)
    {
        $this->path = $path;
        $this->index = $index;
        $this->existing = $existing;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return bool
     */
    public function isExisting()
    {
        return $this->existing;
    }
}
