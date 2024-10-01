<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class DateTimePicker extends Element
{
    #[\Override]
    public function focus()
    {
        $this->open();
    }

    #[\Override]
    public function blur()
    {
        $this->close();
        parent::blur();
    }

    #[\Override]
    public function getValue()
    {
        $value = $this->getDatePicker()->getValue();
        if ($this->hasTimePicker()) {
            $value .= ' ' . $this->getTimePicker()->getValue();
        }

        return $value;
    }

    /**
     * @param string|\DateTime $value
     */
    #[\Override]
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
            if ($this->hasTimePicker()) {
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
    protected function getTimePicker(): ?TimePicker
    {
        if ($this->isBoundToHiddenInput()) {
            return $this->elementFactory->wrapElement(
                'TimePicker',
                $this->getParent()->find('xpath', '/following-sibling::input[contains(@class, "timepicker-input")]')
            );
        }
        if ($this->isBoundToVisibleInput()) {
            return $this->elementFactory->wrapElement(
                'TimePicker',
                $this->find('xpath', '/following-sibling::input[contains(@class, "timepicker-input")]')
            );
        }

        return $this->getElement('TimePicker');
    }

    /**
     * @return NodeElement|null
     */
    protected function getDatePicker()
    {
        if ($this->isBoundToHiddenInput()) {
            return $this->getParent()->find('xpath', '/following-sibling::input[contains(@class, "datepicker-input")]');
        }

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
        $datePicker = $this->getDatePicker();
        $class = $datePicker->getAttribute('class');

        $isOpened = false;
        if ($class !== null) {
            $isOpened = preg_match('/\bui-datepicker-dialog-is-(below|above)\b/', $class) === 1;
        }
        if (!$isOpened && $datePicker->hasAttribute('aria-expanded')) {
            $isOpened = $datePicker->getAttribute('aria-expanded') === 'true';
        }

        return $isOpened;
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

    protected function isBoundToHiddenInput(): bool
    {
        return (bool)$this->getAttribute('data-bound-view');
    }

    protected function isBoundToVisibleInput(): bool
    {
        return $this->getTagName() === 'input' && !$this->hasAttribute('data-bound-view');
    }

    protected function hasTimePicker(): bool
    {
        if ($this->isBoundToHiddenInput()) {
            return (bool)$this->getParent()
                ->findAll('xpath', '/following-sibling::input[contains(@class, "timepicker-input")]');
        }
        if ($this->isBoundToVisibleInput()) {
            return (bool)$this
                ->findAll('xpath', '/following-sibling::input[contains(@class, "timepicker-input")]');
        }

        return (bool)$this->getElements('TimePicker');
    }
}
