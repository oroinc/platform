<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;

class TimePicker extends Element
{
    /**
     * @param \DateTime $dateTime
     */
    public function setValue($dateTime)
    {
        $this->click();

        if ($this->hasAttribute('data-validation')) {
            parent::setValue(new InputValue(InputMethod::TYPE, $dateTime->format('H:i')));
        } else {
            parent::setValue(new InputValue(InputMethod::SET, $dateTime->format('H:i')));
        }

        $this->clickSelectedTime();
    }

    protected function clickSelectedTime()
    {
        $timeSelect = $this->findVisible('css', '.ui-timepicker-wrapper');
        $timeSelect->find('css', 'li.ui-timepicker-selected')->click();
    }
}
