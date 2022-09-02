<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Should be dispatched before or after the denormalization.
 */
class DenormalizeEntityEvent extends Event
{
    private object $object;

    private array $data;

    /**
     * @var array
     *  [
     *      'fieldName1' => true, // Skipped field.
     *      'fieldName2' => false, // Not skipped.
     *  ]
     */
    private array $skippedFields = [];

    /**
     * @param object $object
     * @param array $data
     */
    public function __construct($object, array $data)
    {
        $this->object = $object;
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Marks a field as skipped.
     * Skipped fields should not be taken into account during denormalization.
     */
    public function markAsSkipped(string $fieldName, bool $isSkipped = true): void
    {
        $this->skippedFields[$fieldName] = $isSkipped;
    }

    public function isFieldSkipped(string $fieldName): bool
    {
        return (bool) ($this->skippedFields[$fieldName] ?? false);
    }
}
