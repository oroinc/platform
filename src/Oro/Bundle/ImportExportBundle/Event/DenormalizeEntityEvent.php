<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DenormalizeEntityEvent extends Event
{
    /** @var object */
    protected $object;

    /** @var array */
    protected $data;

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
}
