<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class DateTimePicker extends Element
{
    /**
     * @param string|\DateTime $value
     */
    public function setValue($value)
    {
        $dateTime = $this->getDateTime($value);

        $this->open();
        if (null === $dateTime) {
            $this->setDatePickerValue($value);
        } else {
            $this->getYearPicker()->selectOption($dateTime->format('Y'));
            $monthPicker = $this->getMonthPicker();
            $month = $dateTime->format('M');
            try {
                $monthPicker->selectOption($month);
            } catch (ElementNotFoundException $e) {
                $monthPicker->selectOption(strtolower($month));
            }
            $this->getCalendarDate($dateTime->format('j'))->click();
            if ($this->getElements('TimePicker')) {
                $this->getTimePicker()->setValue($dateTime);
            }
        }
    }

    /**
     * @param string|\DateTime $value
     *
     * @return \DateTime|null
     */
    protected function getDateTime($value)
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        if (0 !== strncmp($value, 'today', 5) && 0 !== strncmp($value, 'now', 3)) {
            try {
                return new \DateTime($value);
            } catch (\Exception $e) {
                // ignore parsing errors
            }
        }

        return null;
    }

    protected function open()
    {
        if (!$this->isOpened()) {
            $this->getDatePicker()->click();
        }
    }

    protected function close()
    {
        if ($this->isOpened()) {
            $this->getDatePicker()->click();
        }
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        $this->open();

        $container = $this->findVisible('css', 'table.ui-datepicker-calendar');

        $header = array_map(
            function (NodeElement $element) {
                return $element->getText();
            },
            $container->findAll('css', 'thead th > span')
        );

        $this->close();

        return $header;
    }

    /**
     * @return NodeElement|null
     */
    protected function getMonthPicker()
    {
        return $this->getDatePickerHeader()->find('css', '.ui-datepicker-month');
    }

    /**
     * @return NodeElement|null
     */
    protected function getYearPicker()
    {
        return $this->getDatePickerHeader()->find('css', '.ui-datepicker-year');
    }

    /**
     * @return NodeElement|null
     */
    protected function getDatePickerHeader()
    {
        return $this->findVisible('css', '.ui-datepicker-header');
    }

    /**
     * @return NodeElement|null
     */
    protected function getCalendar()
    {
        return $this->findVisible('css', '.ui-datepicker-calendar');
    }

    /**
     * @return TimePicker
     */
    protected function getTimePicker()
    {
        return $this->getElement('TimePicker');
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

    /**
     * @return bool
     */
    protected function isOpened()
    {
        $class = $this->getDatePicker()->getAttribute('class');

        if ($class !== null) {
            return preg_match('/\bui-datepicker-dialog-is-(below|above)\b/', $class) === 1;
        }

        return false;
    }

    /**
     * @param string $value
     */
    protected function setDatePickerValue($value)
    {
        $this->closeCalendarWidget();
        $this->getDatePicker()->setValue($value);
    }

    protected function closeCalendarWidget()
    {
        $this->getDatePicker()->click();
    }
}
