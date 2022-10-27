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
        $checkbox = $this->find('css', 'input[type=checkbox]');
        self::assertNotNull($checkbox, 'Can not found actual checkbox element');

        if ('false' === $value || false === $value) {
            if ($checkbox->isChecked()) {
                $this->click();
            }
        } else {
            if ($checkbox->isChecked()) {
                return;
            }

            $this->click();
        }
    }
}
