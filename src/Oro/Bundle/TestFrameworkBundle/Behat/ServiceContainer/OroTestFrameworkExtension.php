<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class OroTestFrameworkExtension implements TestworkExtension
{
    const DUMPER_TAG = 'oro_test.dumper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(Symfony2Extension::KERNEL_ID)->registerBundles();
        $this->processBundleAutoload($container);
        $this->processElements($container);
        $this->processDbDumpers($container);
        $this->processIsolationSubscribers($container);
        $container->get(Symfony2Extension::KERNEL_ID)->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'oro_test';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        /** @var MinkExtension $minkExtension */
        $minkExtension = $extensionManager->getExtension('mink');
        $minkExtension->registerDriverFactory(new OroSelenium2Factory());
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->arrayNode('shared_contexts')
                    ->prototype('scalar')->end()
                    ->info('Contexts that added to all autoload bundles suites')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $loader->load('kernel_services.yml');

        $container->setParameter('oro_test.shared_contexts', $config['shared_contexts']);
    }

    public function processDbDumpers(ContainerBuilder $container)
    {
        $dbDumper = $this->getDbDumper($container);
        $dbDumper->addTag(self::DUMPER_TAG, ['priority' => 100]);
    }

    /**
     * @param ContainerBuilder $container
     * @throws OutOfBoundsException When
     * @throws InvalidArgumentException
     */
    private function processIsolationSubscribers(ContainerBuilder $container)
    {
        $dumpers = [];

        foreach ($container->findTaggedServiceIds(self::DUMPER_TAG) as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $dumpers[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        krsort($dumpers);
        $dumpers = call_user_func_array('array_merge', $dumpers);

        $container->getDefinition('oro_test.listener.feature_isolation_subscriber')->replaceArgument(
            0,
            $dumpers
        );
        $container->getDefinition('oro_test.listener.dump_environment_subscriber')->replaceArgument(
            0,
            $dumpers
        );
    }

    /**
     * @param ContainerBuilder $container
     * @return Definition
     */
    private function getDbDumper(ContainerBuilder $container)
    {
        $driver = $container->get(Symfony2Extension::KERNEL_ID)->getContainer()->getParameter('database_driver');

        $taggedServices = $container->findTaggedServiceIds(
            'oro_test.db_dumper'
        );

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if ($attributes['driver'] !== $driver) {
                    continue;
                }

                return $container->getDefinition($id);
            }
        }

        throw new \InvalidArgumentException(sprintf('You must specify db dumper service for "%s" driver', $driver));
    }

    /**
     * @param $suiteConfigurations
     * @return array
     */
    private function getConfiguredBundles($suiteConfigurations)
    {
        $configuredBundles = [];

        foreach ($suiteConfigurations as $suiteName => $configuration) {
            $settings = $configuration['settings'];
            $type = $configuration['type'];

            if ('symfony_bundle' === $type) {
                $configuredBundles[] = isset($settings['bundle']) ? $settings['bundle'] : $suiteName;
            }
        }
        return $configuredBundles;
    }

    /**
     * Generate behat test suite for every bundle that registered in kernel and not configured in configuration
     *
     * @param ContainerBuilder $container
     */
    private function processBundleAutoload(ContainerBuilder $container)
    {
        $suiteConfigurations = $container->getParameter('suite.configurations');
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);
        /** @var SymfonySuiteGenerator $suiteGenerator */
        $suiteGenerator = $container->get('symfony2_extension.suite.generator');
        $commonContexts = $container->getParameter('oro_test.shared_contexts');

        $configuredBundles = $this->getConfiguredBundles($suiteConfigurations);

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if (in_array($bundle->getName(), $configuredBundles, true)) {
                continue;
            }

            $bundleSuite = $suiteGenerator->generateSuite($bundle->getName(), []);

            if (!$this->hasValidPaths($bundleSuite)) {
                continue;
            }

            $suiteConfigurations[$bundle->getName()] = [
                'type' => 'symfony_bundle',
                'settings' => [
                    'contexts' => $this->getSuiteContexts($bundleSuite, $commonContexts),
                    'paths' => $bundleSuite->getSetting('paths'),
                ],
            ];
        }

        $container->setParameter('suite.configurations', $suiteConfigurations);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processElements(ContainerBuilder $container)
    {
        $elementConfiguration = [];
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            $mappingPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundle->getPath().'/Resources/config/behat_elements.yml'
            );

            if (!is_file($mappingPath)) {
                continue;
            }

            $elementConfiguration = array_merge($elementConfiguration, Yaml::parse(file_get_contents($mappingPath)));
        }

        $container->getDefinition('oro_element_factory')->replaceArgument(1, $elementConfiguration);
    }

    /**
     * @param SymfonyBundleSuite $bundleSuite
     * @param Context[] $commonContexts
     * @return array
     */
    private function getSuiteContexts(SymfonyBundleSuite $bundleSuite, array $commonContexts)
    {
        $suiteContexts = array_filter($bundleSuite->getSetting('contexts'), 'class_exists');
        $suiteContexts = array_merge($suiteContexts, $commonContexts);

        return $suiteContexts;
    }

    /**
     * @param SymfonyBundleSuite $bundleSuite
     * @return bool
     */
    protected function hasValidPaths(SymfonyBundleSuite $bundleSuite)
    {
        return 0 < count(array_filter($bundleSuite->getSetting('paths'), 'is_dir'));
    }

    /**
     * @param BundleInterface $bundle
     * @return bool
     */
    protected function hasDirectory(BundleInterface $bundle, $namespace)
    {
        $path = $bundle->getPath() . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return is_dir($path);
    }
}
