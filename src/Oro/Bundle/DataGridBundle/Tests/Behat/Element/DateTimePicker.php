<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;

class DateTimePicker extends Element
{
    /**
     * @param \DateTime $dateTime
     */
    public function setValue($dateTime)
    {
        $this->getDatePicker()->click();

        $this->getMonthPicker()->selectOption($dateTime->format('M'));
        $this->getYearPicker()->selectOption($dateTime->format('Y'));
        $this->getCalendarDate($dateTime->format('j'))->click();

        /** @var NodeElement $timePicker */
        $timePicker = $this->getTimePicker();
        $timePicker->click();
        $timePicker->setValue(
            new InputValue(InputMethod::TYPE, $dateTime->format('H:i'))
        );
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

    /**
     * @param int|string $dateValue
     * @return NodeElement|null
     */
    protected function getCalendarDate($dateValue)
    {
        return $this->getCalendar()->find('css', "tbody a:contains('$dateValue')");
    }
}
