<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigListener
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ConfigurationManager
     */
    protected $featureConfigManager;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ConfigurationManager $featureConfigManager
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ConfigurationManager $featureConfigManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->featureConfigManager = $featureConfigManager;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        $affectedFeatures = $this->getAllAffectedFeatures($event);
        if ($affectedFeatures) {
            $event = new FeaturesChange();
            $this->eventDispatcher->dispatch(FeaturesChange::NAME, $event);
            
            foreach ($affectedFeatures as $featureName) {
                $event = new FeatureChange();
                $this->eventDispatcher->dispatch(FeatureChange::NAME . '.' . $featureName, $event);
            }
        }
        
    }

    /**
     * @param ConfigUpdateEvent $event
     * @return array
     */
    protected function getAllAffectedFeatures(ConfigUpdateEvent $event)
    {
        $configKeys = array_keys($event->getChangeSet());

        $features = [];
        foreach ($configKeys as $configKey) {
            $features[] = $this->featureConfigManager->getFeatureByToggle($configKey);
        }

        $affectedFeatures = [];
        foreach ($features as $feature) {
            $affectedFeatures[] = $feature;

            foreach ($this->featureConfigManager->getFeatureDependents($feature) as $dependentFeature) {
                $affectedFeatures[] = $dependentFeature;
            }
        }
        
        return array_unique($affectedFeatures);
    }
}
