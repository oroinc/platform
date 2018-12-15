<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

class GridFilterPriceItem extends GridFilterStringItem
{
    /**
     * Select unit type of price filter
     *
     * @param string $filterUnitType
     */
    public function selectUnitType($filterUnitType)
    {
        $this->find('css', 'div.product-price-unit-filter .dropdown-toggle')->click();
        /** @var NodeElement[] $types */
        $types = $this->findAll('css', 'ul.dropdown-menu li a.choice-value');

        foreach ($types as $type) {
            if (preg_match(sprintf('/%s/i', $filterUnitType), $type->getText())) {
                $type->click();
                return;
            }
        }

        self::fail(sprintf('Can\'t find filter with "%s" unit type', $filterUnitType));
    }

    /**
     * Set second value to input field
     *
     * @param string $value
     */
    public function setSecondFilterValue($value)
    {
        $this->find('css', 'div.filter-end input')->setValue($value);
    }
}
