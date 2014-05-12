<?php

namespace Oro\Bundle\UIBundle\Placeholder\Filter;

class Sorter implements PlaceholderFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $items, array $variables)
    {
        usort($items, array($this, 'compareItems'));
        return $items;
    }

    /**
     * Compare function to order blocks using ascending sorting
     *
     * @param array $firstBlock
     * @param array $secondBlock
     * @return int
     */
    public function compareItems($firstBlock, $secondBlock)
    {
        $firstBlockOrder = isset($firstBlock['order']) ? (int)$firstBlock['order'] : 0;
        $secondBlockOrder = isset($secondBlock['order']) ? (int)$secondBlock['order'] : 0;
        return $firstBlockOrder - $secondBlockOrder;
    }
}
