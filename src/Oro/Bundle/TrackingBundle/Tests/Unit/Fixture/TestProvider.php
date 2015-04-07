<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Fixture;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentifierInterface;

class TestProvider implements TrackingEventIdentifierInterface
{
    /**
     * @inheritdoc
     */
    public function isApplicable(TrackingVisit $trackingVisit)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function identify(TrackingVisit $trackingVisit)
    {
        $result = new \stdClass();
        $result->value = 'identity';

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getIdentityTarget()
    {
        return '\stdClassIdentity';
    }

    /**
     * @inheritdoc
     */
    public function isApplicableVisitEvent(TrackingVisitEvent $trackingVisitEvent)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function processEvent(TrackingVisitEvent $trackingVisitEvent)
    {
        $result = new \stdClass();
        $result->value = 'event';

        return [$result];
    }

    /**
     * @inheritdoc
     */
    public function getEventTargets()
    {
        return ['\stdClass'];
    }
}
