<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

/**
 * Builds navigation history item instances.
 *
 * This builder is responsible for creating new navigation history item instances and retrieving
 * existing ones from the database. It extends the abstract builder to provide specialized behavior
 * for history items, which track the user's navigation history within the application.
 */
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
