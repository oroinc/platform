<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * Database query for removing Campaign bundle entities.
 *
 * This query removes all `Campaign`, `EmailCampaign`, and `EmailCampaignStatistics` entities
 * from the database during the cleanup migration when the Campaign bundle is not enabled.
 */
class CleanupCampaignMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    /**
     * @return array
     */
    #[\Override]
    public function getClassNames()
    {
        return [
            'Oro\Bundle\CampaignBundle\Entity\Campaign',
            'Oro\Bundle\CampaignBundle\Entity\EmailCampaign',
            'Oro\Bundle\CampaignBundle\Entity\EmailCampaignStatistics',
        ];
    }
}
