<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess;

class Ticket5775Object
{
    private $property;

    public function getProperty()
    {
        return $this->property;
    }

    private function setProperty()
    {
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
