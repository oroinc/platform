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
     * @return AbstractGridFilterItem|Element
     */
    public function getFilterItem($name, $text, $strict = false)
    {
        $filterItem = $this->findItem($name, $text, $strict);

        if (null === $filterItem || !$filterItem->isValid()) {
            self::fail(sprintf('Can\'t find filter with "%s" name', $text));
        }

        return $filterItem;
    }

    /**
     * @param string $name Element name
     * @param string $text Filter title
     * @param bool $strict
     *
     * @return bool
     */
    public function hasFilterItem($name, $text, $strict = false)
    {
        $filterItem = $this->findItem($name, $text, $strict);

        return $filterItem !== null && $filterItem->isValid();
    }

    /**
     * @param string $name Element name
     * @param string $text Filter title
     * @param bool $strict
     *
     * @return null|AbstractGridFilterItem|Element
     */
    private function findItem($name, $text, $strict = false)
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
                if ($filterItem->isIsset()) {
                    // If more than one filter found perform detailed search by filter label only
                    // For example fill State filter
                    // and there are Country filter containing United States of America string and State filter
                    $selector = $this->selectorManipulator->getContainsXPathSelector(
                        "child::div[(@class " .
                        "and contains(concat(' ', normalize-space(@class), ' '), ' filter-criteria-selector '))]" .
                        "/self::*",
                        $text,
                        false
                    );
                    $filterItem = $this->elementFactory->wrapElement(
                        $name,
                        $filterItem->find($selector['type'], $selector['locator'])->getParent()
                    );
                } else {
                    $filterItem = $this->elementFactory->findElementContainsByXPath($name, $text, true, $this);
                }
            }
        }

        return $filterItem;
    }
}
