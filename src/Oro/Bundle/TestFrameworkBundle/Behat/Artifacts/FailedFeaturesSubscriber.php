<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Storage\FailedFeatures;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Failed feature event subscriber.
 */
class FailedFeaturesSubscriber implements EventSubscriberInterface
{
    public function __construct(protected FailedFeatures $failedFeatureStorage)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FeatureTested::AFTER => ['afterFeatureTested']
        ];
    }

    public function afterFeatureTested(): void
    {
        $this->failedFeatureStorage->clear();
    }
}
