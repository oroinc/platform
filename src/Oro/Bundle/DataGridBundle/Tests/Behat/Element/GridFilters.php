<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridFilters extends Element
{
    /**
     * Find filter by title and wrap it in element
     *
     * @param string $name Element name
     * @param string $text Filter title
     * @param bool $strict
     *
     * @return null|AbstractGridFilterItem|Element
     */
    public function getFilterItem($name, $text, $strict = false)
    {
        $filterItem = null;

        if ($strict) {
            $filterItems = $this->elementFactory->findAllElements($name, $this);
            foreach ($filterItems as $item) {
                if (strcasecmp($item->getText(), $text) === 0) {
                    $filterItem = $item;
                    break;
                }
            }
        } else {
            $filterItem = $this->elementFactory->findElementContains($name, $text, $this);
            if (!$filterItem->isValid()) {
                $filterItem = $this->elementFactory->findElementContainsByXPath($name, $text, true, $this);
            }
        }

        if (null === $filterItem || !$filterItem->isValid()) {
            self::fail(sprintf('Can\'t find filter with "%s" name', $text));
        }

        return $filterItem;
    }
}
