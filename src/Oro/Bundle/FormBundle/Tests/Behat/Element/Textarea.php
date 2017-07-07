<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Textarea extends Element
{
    public function setValue($value)
    {
        $formattedValue = str_replace('\n', PHP_EOL, $value);
        parent::setValue($formattedValue);
    }
}
