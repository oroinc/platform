<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

/**
 * Provides methods to enable and disable API feature.
 * It is expected that this trait will be used in classes that have "getConfigManager()" method.
 */
trait ApiFeatureTrait
{
    /**
     * Enables API feature.
     *
     * @param string $featureName
     */
    protected function enableApiFeature(string $featureName = 'oro_api.web_api'): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set($featureName, true);
        $configManager->flush();
    }

    /**
     * Disables API feature.
     *
     * @param string $featureName
     */
    protected function disableApiFeature(string $featureName = 'oro_api.web_api'): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set($featureName, false);
        $configManager->flush();
    }
}
