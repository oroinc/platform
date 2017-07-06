<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class PrettyCheckbox extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->click();
    }
}
