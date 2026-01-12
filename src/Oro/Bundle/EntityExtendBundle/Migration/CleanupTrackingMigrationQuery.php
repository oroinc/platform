<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * Database query for removing Tracking bundle entities.
 *
 * This query removes all Tracking-related entities (`TrackingData`, `TrackingEvent`,
 * `TrackingEventDictionary`, `TrackingVisit`, `TrackingVisitEvent`, `TrackingWebsite`) from
 * the database during the cleanup migration when the Tracking bundle is not enabled.
 */
class CleanupTrackingMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    /**
     * @return array
     */
    #[\Override]
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
