<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

class GridFilterStringItem extends AbstractGridFilterItem
{
    /**
     * Select type of filter e.g. Contains, Ends With etc.
     *
     * @param string $filterType
     */
    public function selectType($filterType)
    {
        /* Try find select and choice filterType */
        $typeSelect = $this->find('css', 'div.choice-filter select[data-choice-value-select]');

        if (!is_null($typeSelect)) {
            $typeSelect->selectOption($filterType);
            return;
        }

        /* Else try choice filterType in bootstrap dropdown */
        $this->find('css', 'div.choice-filter div.btn-group .dropdown-toggle')->click();
        /** @var NodeElement[] $types */
        $types = $this->findAll('css', 'ul.dropdown-menu li a.choice-value');

        foreach ($types as $type) {
            if (preg_match(sprintf('/%s/i', $filterType), $type->getText())) {
                $type->click();
                return;
            }
        }

        self::fail(sprintf('Can\'t find filter with "%s" type', $filterType));
    }

    /**
     * Get type of filter e.g. Contains, Ends With etc.
     *
     * @return string
     */
    public function getSelectedType()
    {
        $elem = $this->getElement('Chosen Select Option');

        return $elem->getText();
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
