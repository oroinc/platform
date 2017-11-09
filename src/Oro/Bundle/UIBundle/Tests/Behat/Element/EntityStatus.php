<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class EntityStatus extends Element
{
    private $selectorMap = [
        'green' => 'status-enabled',
        'gray'  => 'status-disabled'
    ];

    /**
     * @return string
     */
    public function getColor()
    {
        foreach ($this->selectorMap as $color => $class) {
            if ($this->hasClass($class)) {
                return $color;
            }
        }

        self::fail("Current badge has undefined color, probably you should add it to the map variable");
    }
}
