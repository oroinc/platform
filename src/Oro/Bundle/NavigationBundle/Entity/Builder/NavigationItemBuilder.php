<?php

namespace Oro\Bundle\NavigationBundle\Entity\Builder;

use Oro\Bundle\NavigationBundle\Entity\NavigationItem;

/**
 * Builds navigation item instances.
 *
 * This builder is responsible for creating new navigation item instances and retrieving existing ones
 * from the database. It extends the abstract builder to provide specialized behavior for navigation items,
 * including setting the item type during creation. Navigation items represent user-specific navigation
 * elements such as shortcuts and bookmarks.
 */
class NavigationItemBuilder extends AbstractBuilder
{
    /**
     * Build navigation item
     *
     * @param $params
     * @return NavigationItem|null
     */
    #[\Override]
    public function buildItem($params)
    {
        $navigationItem = new $this->className($params);
        $navigationItem->setType($this->getType());

        return $navigationItem;
    }

    /**
     * Find navigation item
     *
     * @param  int                 $itemId
     * @return NavigationItem|null
     */
    #[\Override]
    public function findItem($itemId)
    {
        return $this->getEntityManager()->find($this->className, $itemId);
    }
}
