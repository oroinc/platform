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
        $this->find('css', 'input.datepicker-input')->click();

        $this->getMonthPicker()->selectOption($dateTime->format('M'));
        $this->getYearPicker()->selectOption($dateTime->format('Y'));
        $dateValue = (string) $dateTime->format('j');

        /** @var NodeElement $date */
        foreach ($this->getCalendar()->findAll('css', 'tbody a') as $date) {
            if ($date->getText() === $dateValue) {
                $date->click();

                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t choose "%s" date', $dateTime->format('Y-M-j')),
            $this->getDriver()
        );
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
}
