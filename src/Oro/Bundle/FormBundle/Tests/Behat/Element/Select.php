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
        if (is_array($value)) {
            if (!$this->hasAttribute('multiple')) {
                self::fail('Only multiple select can be selected by several values');
            }

            foreach ($value as $option) {
                $this->selectOption($option, true);
            }
        } else {
            $this->selectOption($value);
        }
    }
}
