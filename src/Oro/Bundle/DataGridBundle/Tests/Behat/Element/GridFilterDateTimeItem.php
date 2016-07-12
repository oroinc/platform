<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

class GridFilterDateTimeItem extends AbstractGridFilterItem
{
    /**
     * @param \DateTime $dateTime
     * @throws ExpectationException
     */
    public function setStartTime(\DateTime $dateTime)
    {
        $dateTimePicker = $this->createDateTimePicker('div.filter-start-date');
        $dateTimePicker->setValue($dateTime);
    }

    /**
     * @param \DateTime $dateTime
     * @throws ExpectationException
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
     * @param string $locator
     * @return DateTimePicker
     * @throws ExpectationException
     */
    protected function createDateTimePicker($locator)
    {
        $element = $this->find('css', $locator);

        if (!$element) {
            throw new ExpectationException(
                sprintf('Can\'t create datetime picker element with "%s" locator', $locator),
                $this->getDriver()
            );
        }

        return $this->elementFactory->wrapElement('DateTimePicker', $element);
    }
}
