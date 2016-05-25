<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\SearchBundle\Entity\Item;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $result = [];
        /** @var Item $item */
        foreach ($items as $item) {
            $result[] = $this->convertItem($item);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchAliases()
    {
        return ['oro_user'];
    }
}
