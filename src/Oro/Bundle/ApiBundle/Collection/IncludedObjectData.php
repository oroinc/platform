<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class IncludedObjectData
{
    /** @var string */
    private $path;

    /** @var int */
    private $index;

    /** @var bool */
    private $existing;

    /** @var array|null */
    private $normalizedData;

    /** @var EntityMetadata|null */
    private $metadata;

    /**
     * @param string $path     A path to the object in the request data
     * @param int    $index    An index of the object in the included data
     * @param bool   $existing TRUE if an existing object should be updated;
     *                         FALSE if a new object should be created
     */
    public function __construct($path, $index, $existing = false)
    {
        $this->path = $path;
        $this->index = $index;
        $this->existing = $existing;
    }

    /**
     * Gets a path to the object in the request data.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets an index of the object in the included data.
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Gets a value indicates whether an existing object should be updated or new one should be created.
     *
     * @return bool
     */
    public function isExisting()
    {
        return $this->existing;
    }

    /**
     * Gets a normalized representation of the object.
     *
     * @return array|null
     */
    public function getNormalizedData()
    {
        return $this->normalizedData;
    }

    /**
     * Sets a normalized representation of the object.
     *
     * @param array|null $normalizedData
     */
    public function setNormalizedData($normalizedData)
    {
        $this->normalizedData = $normalizedData;
    }

    /**
     * Gets metadata of the object.
     *
     * @return EntityMetadata|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets metadata of the object.
     *
     * @param EntityMetadata|null $metadata
     */
    public function setMetadata(EntityMetadata $metadata = null)
    {
        $this->metadata = $metadata;
    }
}
