<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class GridFilterDateTimeItem extends AbstractGridFilterItem
{
    /**
     * @param \DateTime $dateTime
     */
    public function setStartTime(\DateTime $dateTime)
    {
        $dateTimePicker = $this->createDateTimePicker('div.filter-start-date');
        $dateTimePicker->setValue($dateTime);
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setEndTime(\DateTime $dateTime)
    {
        $dateTimePicker = $this->createDateTimePicker('div.filter-end-date');
        $dateTimePicker->setValue($dateTime);
    }

    /**
     * Select type of filter e.g. between, later than, not equals etc.
     *
     * @param string $filterType
     */
    public function selectType($filterType)
    {
        $this->find('css', '.filter-select-oro')->selectOption($filterType);
    }

    /**
     * Apply filter to the grid
     */
    public function submit()
    {
        $this->find('css', '.filter-update')->click();
    }

    /**
     * @param string $locator
     * @return DateTimePicker
     */
    protected function createDateTimePicker($locator)
    {
        $element = $this->find('css', $locator);

        self::assertNotNull($element, sprintf('Can\'t create datetime picker element with "%s" locator', $locator));

        return $this->elementFactory->wrapElement('DateTimePicker', $element);
    }
}
