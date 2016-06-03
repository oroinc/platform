<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class OroTestFrameworkExtension implements TestworkExtension
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(Symfony2Extension::KERNEL_ID)->registerBundles();
        $this->processBundleAutoload($container);
        $this->processElements($container);
        $this->processDbIsolationSubscribers($container);
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

    /**
     * @param ContainerBuilder $container
     * @throws OutOfBoundsException When
     * @throws InvalidArgumentException
     */
    private function processDbIsolationSubscribers(ContainerBuilder $container)
    {
        $dumper = $this->getDumper($container);
        $container->getDefinition('oro_test.listener.feature_isolation_subscriber')->replaceArgument(
            0,
            $dumper
        );
        $container->getDefinition('oro_test.listener.dump_environment_subscriber')->replaceArgument(
            0,
            $dumper
        );
    }

    /**
     * @param ContainerBuilder $container
     * @return Reference
     */
    private function getDumper(ContainerBuilder $container)
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

                return new Reference($id);
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

            $elementConfiguration = array_merge($elementConfiguration, Yaml::parse($mappingPath));
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
        $suiteContexts = count($suiteContexts) ? $suiteContexts : $commonContexts;

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
