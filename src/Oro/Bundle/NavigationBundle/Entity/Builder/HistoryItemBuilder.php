<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

class HistoryItemBuilder extends AbstractBuilder
{
    /**
     * Build navigation item
     *
     * @param $params
     * @return NavigationHistoryItem|null
     */
    #[\Override]
    public function buildItem($params)
    {
        return new $this->className($params);
    }

    /**
     * Find navigation item
     *
     * @param  int                        $itemId
     * @return NavigationHistoryItem|null
     */
    #[\Override]
    public function findItem($itemId)
    {
        return $this->getEntityManager()->find($this->className, $itemId);
    }
}
