<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

class GridFilterDateTimeItem extends AbstractGridFilterItem
{
    /**
     * @param string|\DateTime $dateTime
     */
    public function setStartTime($dateTime)
    {
        $dateTimePicker = $this->createDateTimePicker('div.filter-start-date');
        $dateTimePicker->setValue($dateTime);
    }

    /**
     * @param string|\DateTime $dateTime
     */
    public function setEndTime($dateTime)
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
        $typeSelect = $this->find('css', '.filter-select-oro-wrapper select');

        if (null !== $typeSelect) {
            $typeSelect->selectOption($filterType);
            return;
        }

        $typeSelect = $this->find(
            'xpath',
            sprintf(
                './/*[contains(concat(" ",normalize-space(@class)," ")," filter-select-oro-wrapper ")]//%s',
                sprintf('label[starts-with(normalize-space(),"%s")]', $filterType)
            ) . '//input[@data-choice-value-select]'
        );

        if (null !== $typeSelect) {
            $typeSelect->click();
            return;
        }
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
