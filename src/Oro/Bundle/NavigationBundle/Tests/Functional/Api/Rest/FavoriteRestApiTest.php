<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationItemData;

/**
 * @dbIsolationPerTest
 */
class FavoriteRestApiTest extends AbstractRestApiTest
{
    protected function getItemType(): string
    {
        return 'favorite';
    }

    protected function getItemId(): int
    {
        return $this->getReference(NavigationItemData::NAVIGATION_ITEM_FAVORITE_1)->getId();
    }
}
