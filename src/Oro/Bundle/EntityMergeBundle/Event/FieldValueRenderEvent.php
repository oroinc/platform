<?php

namespace Oro\Bundle\EntityMergeBundle\Event;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;
use Symfony\Component\EventDispatcher\Event;

class FieldValueRenderEvent extends Event
{
    /**
     * @var mixed
     */
    private $entity;
    /**
     * @var MetadataInterface
     */
    private $metadata;

    /**
     * @var string
     */
    private $fieldValue;

    /**
     * @param $currentFieldValue
     * @param mixed $entity
     * @param MetadataInterface $metadata
     */
    public function __construct(&$currentFieldValue, $entity, MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
        $this->fieldValue = &$currentFieldValue;
        $this->entity = $entity;
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
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * @param string $newValue
     */
    public function setFieldValue($newValue)
    {
        $this->fieldValue = $newValue;
    }
}
