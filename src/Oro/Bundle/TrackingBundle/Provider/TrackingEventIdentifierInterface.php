<?php

namespace Oro\Bundle\TrackingBundle\Provider;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;

interface TrackingEventIdentifierInterface
{
    /**
     * Checks if given tracking event is supported by identifier
     *
     * @param  TrackingVisit $trackingVisit
     * @return bool
     */
    public function isApplicable(TrackingVisit $trackingVisit);

    /**
     * Returns entity object which should be associated with given event.
     * While identifying we can reach 3 cases:
     *  - targetUID is NOT detected or error with parsing
     *      - will return NULL
     *  - targetUID is detected but targetObject NOT
     *      - will return array ['targetUID' = {parsedUID}, 'targetObject' = NULL]
     *  - both targetUID is detected and targetObject is found
     *      - will return array ['targetUID' = {parsedUID}, 'targetObject' = {object}]
     *
     * @param TrackingVisit $trackingVisit
     * @return array|null
     */
    public function identify(TrackingVisit $trackingVisit);

    /**
     * Returns FQCN for identifying entity.
     *
     * @return string
     */
    public function getIdentityTarget();

    /**
     * @param TrackingVisitEvent $trackingVisitEvent
     * @return bool
     */
    public function isApplicableVisitEvent(TrackingVisitEvent $trackingVisitEvent);

    /**
     * Return array with target objects we should associate current tracking visit event
     *
     * @param TrackingVisitEvent $trackingVisitEvent
     * @return array
     */
    public function processEvent(TrackingVisitEvent $trackingVisitEvent);

    /**
     * Returns array with FQCN for identifying events.
     *
     * @return string
     */
    public function getEventTargets();
}
