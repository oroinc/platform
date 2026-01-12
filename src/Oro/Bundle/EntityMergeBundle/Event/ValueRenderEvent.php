<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a field value is being rendered for display during merge.
 *
 * This event allows listeners to intercept and modify how field values are converted
 * to their display representation. Listeners can access the original value, the converted
 * (rendered) value, and the field metadata to customize the rendering based on field type
 * or other contextual information.
 */
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
