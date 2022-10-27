<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;

/**
 * Provides methods to enable and disable API feature.
 */
trait ApiFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    /**
     * Enables API feature.
     */
    private function enableApiFeature(string $featureName = 'oro_api.web_api'): void
    {
        $configManager = self::getConfigManager();
        $configManager->set($featureName, true);
        $configManager->flush();
    }

    /**
     * Disables API feature.
     */
    private function disableApiFeature(string $featureName = 'oro_api.web_api'): void
    {
        $configManager = self::getConfigManager();
        $configManager->set($featureName, false);
        $configManager->flush();
    }
}
