<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class DateTimePicker extends Element
{
    /**
     * @param \DateTime $dateTime
     * @throws ExpectationException
     */
    public function setValue($dateTime)
    {
        $this->getDatePicker()->click();

        $this->getMonthPicker()->selectOption($dateTime->format('M'));
        $this->getYearPicker()->selectOption($dateTime->format('Y'));
        $dateValue = (string) $dateTime->format('j');

        /** @var NodeElement $date */
        foreach ($this->getCalendar()->findAll('css', 'tbody a') as $date) {
            if ($date->getText() === $dateValue) {
                $date->click();
            }
        }

        $timePicker = $this->getTimePicker();
        $timePicker->setValue($dateTime->format('H:i'));
        $timePicker->click();
        $this->clickSelectedTime();
    }

    protected function clickSelectedTime()
    {
        $timeSelect = $this->findVisible('css', '.ui-timepicker-wrapper');
        $timeSelect->find('css', 'li.ui-timepicker-selected')->click();
    }

    /**
     * @return NodeElement|null
     */
    protected function getMonthPicker()
    {
        return $this->findVisible('css', '.ui-datepicker-month');
    }

    /**
     * @return NodeElement|null
     */
    protected function getYearPicker()
    {
        return $this->findVisible('css', '.ui-datepicker-year');
    }

    /**
     * @return NodeElement|null
     */
    protected function getCalendar()
    {
        return $this->findVisible('css', '.ui-datepicker-calendar');
    }

    /**
     * @return NodeElement|null
     */
    protected function getTimePicker()
    {
        return $this->find('css', 'input.timepicker-input');
    }

    /**
     * @return NodeElement|null
     */
    protected function getDatePicker()
    {
        return $this->find('css', 'input.datepicker-input');
    }
}
