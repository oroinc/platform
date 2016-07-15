<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

class GridFilterStringItem extends AbstractGridFilterItem
{
    /**
     * Select type of filter e.g. Contains, Ends With etc.
     *
     * @param string $filterType
     * @throws ExpectationException
     */
    public function selectType($filterType)
    {
        $this->find('css', 'div.choice-filter div.btn-group .dropdown-toggle')->click();
        /** @var NodeElement[] $types */
        $types = $this->findAll('css', 'ul.dropdown-menu li a.choice-value');

        foreach ($types as $type) {
            if (preg_match(sprintf('/%s/i', $filterType), $type->getText())) {
                $type->click();
                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t find filter with "%s" type', $filterType),
            $this->getDriver()
        );
    }

    /**
     * Set value to input field
     *
     * @param string $value
     */
    public function setFilterValue($value)
    {
        $this->find('css', 'div.value-field-frame input')->setValue($value);
    }

    /**
     * Apply filter to the grid
     */
    public function submit()
    {
        $this->find('css', '.filter-update')->click();
    }
}
