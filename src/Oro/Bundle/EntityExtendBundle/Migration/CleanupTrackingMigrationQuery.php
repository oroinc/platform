<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

class CleanupTrackingMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    /**
     * @return array
     */
    public function getClassNames()
    {
        return [
            'Oro\Bundle\TrackingBundle\Entity\TrackingData',
            'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
            'Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary',
            'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
            'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite',
        ];
    }
}
