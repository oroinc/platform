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
    public function setValue(\DateTime $dateTime)
    {
        $this->find('css', 'input.datepicker-input')->click();
        $page = $this->getPage();

        $this->getMonthPicker()->selectOption($dateTime->format('M'));
        $this->getYearPicker()->selectOption($dateTime->format('Y'));
        $dateValue = (string) $dateTime->format('j');

        /** @var NodeElement $date */
        foreach ($this->getCalendar()->findAll('css', 'tbody a') as $date) {
            if ($date->getText() == $dateValue) {
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
        return array_shift(array_filter(
            $this->getPage()->findAll('css', '.ui-datepicker-month'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        ));
    }

    /**
     * @return NodeElement|null
     */
    protected function getYearPicker()
    {
        return array_shift(array_filter(
            $this->getPage()->findAll('css', '.ui-datepicker-year'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        ));
    }

    /**
     * @return NodeElement|null
     */
    protected function getCalendar()
    {
        return array_shift(array_filter(
            $this->getPage()->findAll('css', '.ui-datepicker-calendar'),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        ));
    }
}
