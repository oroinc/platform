<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;

class TimePicker extends Element
{
    /**
     * todo: Fix with selenium2 BAP-13992
     * @param \DateTime $dateTime
     */
    public function setValue($dateTime)
    {
        $this->click();

        if ($this->hasAttribute('data-validation')) {
            $timeSelect = $this->getPage()->findVisible('css', '.ui-timepicker-wrapper');
            $time = $dateTime->format('g:i A');
            /** @var NodeElement $li */
            foreach ($timeSelect->findAll('css', 'li') as $li) {
                if ($time == $li->getText()) {
                    $li->mouseOver();
                    $li->click();
                }
            }
        } else {
            parent::setValue(new InputValue(InputMethod::SET, $dateTime->format('g:i')));
            $this->clickSelectedTime();
        }
    }

    protected function clickSelectedTime()
    {
        $timeSelect = $this->getPage()->findVisible('css', '.ui-timepicker-wrapper');
        $timeSelect->find('css', 'li.ui-timepicker-selected')->click();
    }
}
