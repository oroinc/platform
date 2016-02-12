<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class NormalizeEntityEvent extends Event
{
    /** @var object */
    protected $object;

    /** @var array result */
    protected $result;

    /**
     * @param object $object
     * @param array $result
     */
    public function __construct($object, array $result)
    {
        $this->object = $object;
        $this->result = $result;
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
     * @param string $name
     * @param array $value
     */
    public function setResultField($name, array $value)
    {
        $this->result[$name] = $value;
    }
}
