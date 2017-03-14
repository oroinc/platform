<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait EmailFeatureTrait
{
    public function enableEmailFeature()
    {
        $this->getContainer()->get('oro_config.manager')
            ->set(Configuration::getConfigKeyByName('feature_enabled'), true);
        $this->getContainer()->get('oro_config.manager')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }


    public function disableEmailFeature()
    {
        $this->getContainer()->get('oro_config.manager')
            ->set(Configuration::getConfigKeyByName('feature_enabled'), false);
        $this->getContainer()->get('oro_config.manager')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }
}
