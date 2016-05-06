<?php
namespace Oro\Component\Messaging\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

class MessageEvent extends Event
{
    /**
     * @var array|string|number
     */
    protected $values;

    /**
     * @param array|string|number $values
     */
    public function __construct($values = '')
    {
        $this->values = $values;
    }

    /**
     * @return array|string|number
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array|string|number $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }
}
