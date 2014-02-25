<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;

class ValueRenderEvent extends Event
{
    /**
     * @var mixed
     */
    private $originalValue;

    /**
     * @var MetadataInterface
     */
    private $metadata;

    /**
     * @var string
     */
    private $convertedValue;

    /**
     * @param mixed $convertedValue
     * @param mixed $originalValue
     * @param MetadataInterface $metadata
     */
    public function __construct($convertedValue, $originalValue, MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
        $this->convertedValue = $convertedValue;
        $this->originalValue = $originalValue;
    }

    /**
     * @return MetadataInterface
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return mixed
     */
    public function getOriginalValue()
    {
        return $this->originalValue;
    }

    /**
     * @return string
     */
    public function getConvertedValue()
    {
        return $this->convertedValue;
    }

    /**
     * @param string $newValue
     */
    public function setConvertedValue($newValue)
    {
        $this->convertedValue = $newValue;
    }
}
