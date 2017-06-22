<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Select extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->selectOption($value);
    }
}
