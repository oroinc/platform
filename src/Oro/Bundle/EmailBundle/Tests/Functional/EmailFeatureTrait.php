<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait EmailFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    public function enableEmailFeature(): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(Configuration::getConfigKeyByName('feature_enabled'), true);
        $configManager->flush();

        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function disableEmailFeature(): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(Configuration::getConfigKeyByName('feature_enabled'), false);
        $configManager->flush();

        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }
}
