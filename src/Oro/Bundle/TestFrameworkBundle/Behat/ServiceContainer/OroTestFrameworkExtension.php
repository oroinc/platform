<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class OroTestFrameworkExtension implements TestworkExtension
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(Symfony2Extension::KERNEL_ID)->registerBundles();
        $this->processBundleAutoload($container);
        $this->processPageObjectsAutoload($container);
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
                ->scalarNode('elements_namespace_suffix')
                    ->defaultValue('\Tests\Behat\Page\Element')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_test.shared_contexts', $config['shared_contexts']);
        $container->setParameter('oro_test.elements_namespace_suffix', $config['elements_namespace_suffix']);
        $this->loadDbIsolationSubscriber($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadDbIsolationSubscriber(ContainerBuilder $container)
    {
        $definition = new Definition('Oro\Bundle\TestFrameworkBundle\Behat\Listener\DbIsolationSubscriber', [
            new Reference(Symfony2Extension::KERNEL_ID)
        ]);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 10]);
        $container->setDefinition('oro_test.listener.db_isolation_subscriber', $definition);
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
        $symfonySuiteGenerator = $container->get('symfony2_extension.suite.generator');
        $commonContexts = $container->getParameter('oro_test.shared_contexts');

        $configuredBundles = $this->getConfiguredBundles($suiteConfigurations);

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if (in_array($bundle->getName(), $configuredBundles)) {
                continue;
            }

            $bundleSuite = $symfonySuiteGenerator->generateSuite($bundle->getName(), []);

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
    private function processPageObjectsAutoload(ContainerBuilder $container)
    {
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);
        $elements = $container->getParameter('sensio_labs.page_object_extension.namespaces.element');
        $elementsNamespaceSuffix = $container->getParameter('oro_test.elements_namespace_suffix');

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if ($this->hasDirectory($bundle, $elementsNamespaceSuffix)) {
                $elementNamespace = $bundle->getNamespace().$elementsNamespaceSuffix;

                if (!in_array($elementNamespace, $elements)) {
                    $elements[] = $elementNamespace;
                }
            }
        }

        $container->setParameter('sensio_labs.page_object_extension.namespaces.element', $elements);
    }

    /**
     * @param SymfonyBundleSuite $bundleSuite
     * @param Context[] $commonContexts
     * @return array
     */
    private function getSuiteContexts(SymfonyBundleSuite $bundleSuite, array $commonContexts)
    {
        $suiteContexts = array_filter($bundleSuite->getSetting('contexts'), "class_exists");
        $suiteContexts = count($suiteContexts) ? $suiteContexts : $commonContexts;

        return $suiteContexts;
    }

    /**
     * @param SymfonyBundleSuite $bundleSuite
     * @return bool
     */
    protected function hasValidPaths(SymfonyBundleSuite $bundleSuite)
    {
        return 0 < count(array_filter($bundleSuite->getSetting('paths'), "is_dir"));
    }

    /**
     * @param BundleInterface $bundle
     * @return bool
     */
    protected function hasDirectory(BundleInterface $bundle, $namespace)
    {
        $path = $bundle->getPath().str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return is_dir($path);
    }
}
