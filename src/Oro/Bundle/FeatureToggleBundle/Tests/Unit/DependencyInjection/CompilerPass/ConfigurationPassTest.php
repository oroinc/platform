<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $configurationDefinition = $container->register(
            'oro_featuretoggle.configuration',
            FeatureToggleConfiguration::class
        );

        $container->register('testConfigExtension')
            ->addTag('oro_feature.config_extension');

        $compiler = new ConfigurationPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addExtension', [new Reference('testConfigExtension')]]
            ],
            $configurationDefinition->getMethodCalls()
        );
    }
}
