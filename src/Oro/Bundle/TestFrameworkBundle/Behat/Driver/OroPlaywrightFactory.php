<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Factory to build the Playwright-based Mink driver.
 */
class OroPlaywrightFactory implements DriverFactory
{
    #[\Override]
    public function getDriverName()
    {
        return 'oroPlaywright';
    }

    #[\Override]
    public function supportsJavascript()
    {
        return true;
    }

    #[\Override]
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('browser_type')->defaultValue('chromium')->end()
                ->booleanNode('headless')->defaultTrue()->end()
                ->variableNode('launch_options')->defaultValue([])->end()
                ->arrayNode('viewport')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('width')->defaultValue(1920)->end()
                        ->integerNode('height')->defaultValue(1080)->end()
                    ->end()
                ->end()
            ->end();
    }

    #[\Override]
    public function buildDriver(array $config)
    {
        $definition = new Definition(OroPlaywrightDriver::class, [
            $config['browser_type'],
            $config['headless'],
            $config['launch_options'],
            $config['viewport'],
        ]);
        $definition->addMethodCall(
            'setScreenshotGenerator',
            [new Reference('oro_test.artifacts.screenshot_generator')]
        );
        $definition->addMethodCall('setBrowsersPath', ['%kernel.project_dir%/var/ms-playwright']);

        return $definition;
    }
}
