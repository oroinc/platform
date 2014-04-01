<?php

namespace Oro\Bundle\UIBundle\Placeholder\Filter;

interface PlaceholderFilterInterface
{
    /**
     * Filter placeholder items
     *
     * @param array $items
     * @param array $variables
     * @return array
     */
    public function filter(array $items, array $variables);
}
