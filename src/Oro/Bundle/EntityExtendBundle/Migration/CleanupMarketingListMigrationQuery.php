<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

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
