<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Resets the feature checker cache after a system configuration option associated with a feature is changed
 * and after the system configuration scope is changed.
 * Dispatches "oro_featuretoggle.feature.change" and "oro_featuretoggle.feature.change.{feature_name}" events
 * after a system configuration option associated with a feature is changed.
 */
class ConfigListener
{
    private EventDispatcherInterface $eventDispatcher;
    private ConfigurationManager $featureConfigManager;
    private FeatureChecker $featureChecker;
    private array $featuresStates = [];
    private array $affectedFeatures = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ConfigurationManager $featureConfigManager,
        FeatureChecker $featureChecker
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->featureConfigManager = $featureConfigManager;
        $this->featureChecker = $featureChecker;
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event): void
    {
        $this->affectedFeatures = $this->getAllAffectedFeatures(array_keys($event->getSettings()));
        $this->featuresStates = $this->getFeaturesStates();
    }

    public function onUpdateAfter(): void
    {
        $this->featureChecker->resetCache();

        $changedFeatures = array_diff_assoc($this->getFeaturesStates(), $this->featuresStates);
        if ($changedFeatures) {
            $this->eventDispatcher->dispatch(
                new FeaturesChange($changedFeatures),
                FeaturesChange::NAME
            );
            foreach ($changedFeatures as $featureName => $state) {
                $this->eventDispatcher->dispatch(
                    new FeatureChange($featureName, $state),
                    FeatureChange::NAME . '.' . $featureName
                );
            }
        }
        $this->affectedFeatures = [];
        $this->featuresStates = [];
    }

    public function onScopeIdChange(): void
    {
        $this->featureChecker->resetCache();
    }

    private function getFeaturesStates(): array
    {
        $featuresStates = [];
        foreach ($this->affectedFeatures as $feature) {
            $featuresStates[$feature] = $this->featureChecker->isFeatureEnabled($feature);
        }

        return $featuresStates;
    }

    private function getAllAffectedFeatures(array $configKeys): array
    {
        $affectedFeatures = [];
        foreach ($configKeys as $configKey) {
            $feature = $this->featureConfigManager->getFeatureByToggle($configKey);
            if ($feature) {
                $affectedFeatures[] = [$feature];
                $affectedFeatures[] = $this->featureConfigManager->getFeatureDependents($feature);
            }
        }

        return array_unique(array_merge(...$affectedFeatures));
    }
}
