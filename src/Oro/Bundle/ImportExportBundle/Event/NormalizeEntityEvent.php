<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Should be dispatched before or after the normalization.
 */
class NormalizeEntityEvent extends Event
{
    /** @var object */
    protected $object;

    /** @var array result */
    protected $result;

    /** @var bool */
    protected $fullData;

    /**
     * @param object $object
     * @param array $result
     * @param bool $fullData
     */
    public function __construct($object, array $result, $fullData = false)
    {
        $this->object = $object;
        $this->result = $result;
        $this->fullData = $fullData;
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
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return bool
     */
    public function isFullData()
    {
        return $this->fullData;
    }

    public function setResultFieldValue(string $name, mixed $value): void
    {
        $this->result[$name] = $value;
    }
}
