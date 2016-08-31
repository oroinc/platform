<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var array
     */
    protected $featuresStates = [];

    /**
     * @var array
     */
    protected $affectedFeatures = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ConfigurationManager $featureConfigManager
     * @param FeatureChecker $featureChecker
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ConfigurationManager $featureConfigManager,
        FeatureChecker $featureChecker
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->featureConfigManager = $featureConfigManager;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $configKeys = array_keys($event->getSettings());
        $this->affectedFeatures = $this->getAllAffectedFeatures($configKeys);

        $this->featuresStates = $this->getFeaturesStates();
    }

    public function onUpdateAfter()
    {
        $this->featureChecker->resetCache();
        $changedFeaturesStates = $this->getFeaturesStates();

        $changedFeatures = array_diff_assoc($changedFeaturesStates, $this->featuresStates);

        if ($changedFeatures) {
            $event = new FeaturesChange($changedFeatures);
            $this->eventDispatcher->dispatch(FeaturesChange::NAME, $event);
            
            foreach ($changedFeatures as $featureName => $state) {
                $event = new FeatureChange($featureName, $state);
                $this->eventDispatcher->dispatch(FeatureChange::NAME . '.' . $featureName, $event);
            }
        }
    }

    /**
     * @return array
     */
    protected function getFeaturesStates()
    {
        $featuresStates = [];
        foreach ($this->affectedFeatures as $feature) {
            $featuresStates[$feature] = $this->featureChecker->isFeatureEnabled($feature);
        }

        return $featuresStates;
    }

    /**
     * @param array $configKeys
     * @return array
     */
    protected function getAllAffectedFeatures(array $configKeys)
    {
        $features = [];
        foreach ($configKeys as $configKey) {
            $feature = $this->featureConfigManager->getFeatureByToggle($configKey);
            if ($feature) {
                $features[] = $feature;
            }
        }

        $affectedFeatures = [];
        foreach ($features as $feature) {
            $affectedFeatures[] = $feature;

            $dependentFeatures = $this->featureConfigManager->getFeatureDependents($feature);
            $affectedFeatures = array_merge($affectedFeatures, $dependentFeatures, [$feature]);
        }
        
        return array_unique($affectedFeatures);
    }
}
