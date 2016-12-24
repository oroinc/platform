<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

class CleanupCampaignMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    /**
     * @return array
     */
    public function getClassNames()
    {
        return [
            'Oro\Bundle\CampaignBundle\Entity\Campaign',
            'Oro\Bundle\CampaignBundle\Entity\EmailCampaign',
            'Oro\Bundle\CampaignBundle\Entity\EmailCampaignStatistics',
        ];
    }
}
