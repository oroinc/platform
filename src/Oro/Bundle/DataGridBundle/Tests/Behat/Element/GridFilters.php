<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class GridFilters extends Element
{
    /**
     * Find filter by title and wrap it in element
     *
     * @param string $name Element name
     * @param string $locator Filter title
     *
     * @return null|AbstractGridFilterItem
     * @throws ExpectationException
     */
    public function getFilterItem($name, $locator)
    {
        $filterItem = $this->tryFindFilterItem($name, $locator);

        if (!$filterItem) {
            throw new ExpectationException(
                sprintf('Can\'t find filter with "%s" name', $locator),
                $this->getDriver()
            );
        }

        return $filterItem;
    }

    /**
     * @param string $locator
     * @return AbstractGridFilterItem|null
     */
    public function tryFindFilterItem($name, $locator)
    {
        foreach ($this->filterLocatorVariants($locator) as $filterLocatorVariant) {
            if ($filterItem = $this->find('css', sprintf('div.filter-item:contains("%s")', $filterLocatorVariant))) {
                return $this->elementFactory->wrapElement($name, $filterItem);
            }
        }

        return null;
    }

    /**
     * @param string $locator
     * @return \Generator
     */
    protected function filterLocatorVariants($locator)
    {
        yield $locator;
        yield ucwords(strtolower($locator));
        yield ucfirst(strtolower($locator));
    }
}
