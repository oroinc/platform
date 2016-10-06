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
     *
     * @return null|AbstractGridFilterItem
     */
    public function getFilterItem($name, $text)
    {
        $filterItem = $this->elementFactory->findElementContains($name, $text);
        self::assertTrue($filterItem->isValid(), sprintf('Can\'t find filter with "%s" name', $text));

        return $filterItem;
    }
}
