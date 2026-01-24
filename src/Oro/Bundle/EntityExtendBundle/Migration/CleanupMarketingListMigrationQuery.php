<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * Database query for removing MarketingList bundle entities.
 *
 * This query removes all `MarketingList`, `MarketingListType`, and `MarketingListItem` entities
 * from the database during the cleanup migration when the MarketingList bundle is not enabled.
 */
class CleanupMarketingListMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    /**
     * @return array
     */
    #[\Override]
    public function getClassNames()
    {
        return [
            'Oro\Bundle\MarketingListBundle\Entity\MarketingList',
            'Oro\Bundle\MarketingListBundle\Entity\MarketingListType',
            'Oro\Bundle\MarketingListBundle\Entity\MarketingListItem',
        ];
    }
}
