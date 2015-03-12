<?php

namespace Oro\Bundle\TrackingBundle\Provider;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;

class TrackingEventIdentificationProvider
{
    /** @var  TrackingEventIdentifierInterface[] */
    protected $providers;

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
    public function getTargetEntities()
    {
        $targetEntityClassses = [];
        foreach ($this->providers as $provider) {
            $targetEntityClassses[] = $provider->getTarget();
        }

        return $targetEntityClassses;
    }
}
