<?php

namespace Oro\Bundle\TrackingBundle\Provider;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;

class TrackingEventIdentificationProvider
{
    /** @var TrackingEventIdentifierInterface[] */
    protected $providers = [];

    /**
     * Add activity list provider
     *
     * @param TrackingEventIdentifierInterface $provider
     */
    public function addProvider(TrackingEventIdentifierInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Returns identifying object for given tracking visit.
     *
     * @param TrackingVisit $trackingVisit
     *
     * @return object|bool
     */
    public function identify(TrackingVisit $trackingVisit)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($trackingVisit)) {
                return $provider->identify($trackingVisit);
            }
        }

        return false;
    }

    /**
     * Returns array of possible identifying object classes.
     *
     * @return array
     */
    public function getTargetIdentityEntities()
    {
        $targetEntityClasses = [];
        foreach ($this->providers as $provider) {
            $targetEntityClasses[] = $provider->getIdentityTarget();
        }

        return $targetEntityClasses;
    }

    /**
     * @return array
     */
    public function getEventTargetEntities()
    {
        $targetEntityClasses = [];
        foreach ($this->providers as $provider) {
            $targetEntityClasses = array_merge($targetEntityClasses, $provider->getEventTargets());
        }

        return array_unique($targetEntityClasses);
    }

    /**
     * @param TrackingVisitEvent $trackingVisitEvent
     * @return array
     */
    public function processEvent(TrackingVisitEvent $trackingVisitEvent)
    {
        $targets = [];
        foreach ($this->providers as $provider) {
            if ($provider->isApplicableVisitEvent($trackingVisitEvent)) {
                $targets = array_merge($targets, $provider->processEvent($trackingVisitEvent));
            }
        }

        return array_filter($targets);
    }
}
