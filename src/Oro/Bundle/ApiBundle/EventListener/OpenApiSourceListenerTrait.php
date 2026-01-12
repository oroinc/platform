<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;

/**
 * Contains methods to check if API documentation need to be updated.
 */
trait OpenApiSourceListenerTrait
{
    /** @var string[] */
    private array $excludedFeatures;

    private function isApplicableFeaturesChanged(FeaturesChange $event): bool
    {
        $numberOfChangedExcludedFeatures = 0;
        $changeSet = $event->getChangeSet();
        foreach ($this->excludedFeatures as $featureName) {
            if (\array_key_exists($featureName, $changeSet)) {
                $numberOfChangedExcludedFeatures++;
            }
        }

        return
            0 === $numberOfChangedExcludedFeatures
            || \count($changeSet) > $numberOfChangedExcludedFeatures;
    }

    private function isApplicableEntityConfigsChanged(PostFlushConfigEvent $event): bool
    {
        $models = $event->getModels();
        foreach ($models as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }
            $fieldConfig = $model->toArray('extend');
            if (
                ($fieldConfig['is_serialized'] ?? false)
                || ExtendScope::STATE_ACTIVE === $fieldConfig['state']
            ) {
                return true;
            }
        }

        return false;
    }
}
