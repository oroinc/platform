<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

class OroTestFrameworkExtension implements TestworkExtension
{
    const ELEMENT_FACTORY_ID = 'oro_element_factory';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(Symfony2Extension::KERNEL_ID)->boot();
        $this->processBundleAutoload($container);
        $this->processElementFactory($container);
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
        $container->setParameter('oro_test.shared_contexts', $config['shared_contexts']);
        $this->loadElementFactoryInitializer($container);
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
    private function processElementFactory(ContainerBuilder $container)
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

        $elementFactory = new Definition(
            'Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory',
            [
                new Reference(MinkExtension::MINK_ID),
                $elementConfiguration,
            ]
        );
        $container->setDefinition(self::ELEMENT_FACTORY_ID, $elementFactory);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadElementFactoryInitializer(ContainerBuilder $container)
    {
        $formFillerAwareInitializerDefinition = new Definition(
            'Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer\ElementFactoryInitializer',
            [new Reference(self::ELEMENT_FACTORY_ID)]
        );
        $formFillerAwareInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 0));
        $container->setDefinition('oro_behat_form_filler_initializer', $formFillerAwareInitializerDefinition);
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
