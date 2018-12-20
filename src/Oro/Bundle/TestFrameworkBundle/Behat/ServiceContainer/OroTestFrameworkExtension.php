<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Symfony2Extension\Suite\SymfonyBundleSuite;
use Behat\Symfony2Extension\Suite\SymfonySuiteGenerator;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ArtifactsHandlerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Factory;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MessageQueueIsolatorInterface;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Basic behat extension that contains logic which prepare environment while testing, load configuration, etc.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroTestFrameworkExtension implements TestworkExtension
{
    const ISOLATOR_TAG = 'oro_behat.isolator';
    const SUITE_AWARE_TAG = 'suite_aware';
    const HEALTH_CHECKER_TAG = 'behat_health_checker';
    const HEALTH_CHECKER_AWARE_TAG = 'health_checker_aware';
    const CONFIG_PATH = '/Tests/Behat/behat.yml';
    const ELEMENTS_CONFIG_ROOT = 'elements';
    const PAGES_CONFIG_ROOT = 'pages';
    const SUITES_CONFIG_ROOT = 'suites';

    /**
     * @var ServiceProcessor
     */
    private $processor;

    /**
     * Initializes compiler pass.
     *
     * @param null|ServiceProcessor $processor
     */
    public function __construct(ServiceProcessor $processor = null)
    {
        $this->processor = $processor ? : new ServiceProcessor();
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(Symfony2Extension::KERNEL_ID)->registerBundles();
        $this->transferApplicationParameters($container);
        $this->processBundleBehatConfigurations($container);
        $this->processBundleAutoload($container);
        $this->processReferenceRepositoryInitializers($container);
        $this->processIsolationSubscribers($container);
        $this->processSuiteAwareSubscriber($container);
        $this->processClassResolvers($container);
        $this->processArtifactHandlers($container);
        $this->processHealthCheckers($container);
        $this->replaceSessionListener($container);
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
                ->variableNode('shared_contexts')
                    ->info('Contexts that added to all autoload bundles suites')
                    ->defaultValue([])
                ->end()
                ->arrayNode('artifacts')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('handlers')
                        ->useAttributeAsKey('name')
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
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
        $loader->load('health_checkers.yml');
        $loader->load('isolators.yml');
        $loader->load('artifacts.yml');
        $loader->load('cli_controllers.yml');
        $loader->load('kernel_services.yml');

        $container->setParameter('oro_test.shared_contexts', $config['shared_contexts']);
        $container->setParameter('oro_test.artifacts.handler_configs', $config['artifacts']['handlers']);
        // Remove reboot kernel after scenario because we have isolation in feature layer instead of scenario
        $container->getDefinition('symfony2_extension.context_initializer.kernel_aware')
            ->clearTag(EventDispatcherExtension::SUBSCRIBER_TAG);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function transferApplicationParameters(ContainerBuilder $container)
    {
        /** @var KernelInterface $kernel */
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);
        $container->setParameter('kernel.log_dir', $kernel->getLogDir());
        $container->setParameter('kernel.root_dir', $kernel->getRootDir());
        $container->setParameter('kernel.project_dir', $kernel->getProjectDir());
        $container->setParameter('kernel.secret', $kernel->getContainer()->getParameter('kernel.secret'));
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processIsolationSubscribers(ContainerBuilder $container)
    {
        $isolators = [];
        $applicationContainer = $container->get(Symfony2Extension::KERNEL_ID)->getContainer();

        foreach ($container->findTaggedServiceIds(self::ISOLATOR_TAG) as $id => $attributes) {
            /** @var IsolatorInterface $isolator */
            $isolator = $container->get($id);

            if ($isolator->isApplicable($applicationContainer)) {
                $reference = new Reference($id);

                foreach ($attributes as $attribute) {
                    $priority = isset($attribute['priority']) ? $attribute['priority'] : 0;
                    $isolators[$priority][] = $reference;
                }
            }
        }

        // sort by priority and flatten
        krsort($isolators);
        $isolators = call_user_func_array('array_merge', $isolators);

        $container->getDefinition('oro_behat_extension.isolation.test_isolation_subscriber')->replaceArgument(
            0,
            $isolators
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processArtifactHandlers(ContainerBuilder $container)
    {
        $handlerConfigurations = $container->getParameter('oro_test.artifacts.handler_configs');
        $prettySubscriberDefinition = $container->getDefinition('oro_test.artifacts.pretty_artifacts_subscriber');
        $progressSubscriberDefinition = $container->getDefinition('oro_test.artifacts.progress_artifacts_subscriber');

        foreach ($container->findTaggedServiceIds('artifacts_handler') as $id => $attributes) {
            $handlerClass = $container->getDefinition($id)->getClass();

            if (!in_array(ArtifactsHandlerInterface::class, class_implements($handlerClass), true)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" should implement "%s"',
                    $handlerClass,
                    ArtifactsHandlerInterface::class
                ));
            }

            /** @var ArtifactsHandlerInterface $handlerClass */
            if (empty($handlerConfigurations[$handlerClass::getConfigKey()])) {
                continue;
            }

            if (false === $handlerConfigurations[$handlerClass::getConfigKey()]) {
                continue;
            }

            $container->getDefinition($id)->replaceArgument(0, $handlerConfigurations[$handlerClass::getConfigKey()]);
            $prettySubscriberDefinition->addMethodCall('addArtifactHandler', [new Reference($id)]);
            $progressSubscriberDefinition->addMethodCall('addArtifactHandler', [new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processHealthCheckers(ContainerBuilder $container)
    {
        $healthCheckerIds = array_keys($container->findTaggedServiceIds(self::HEALTH_CHECKER_TAG));

        foreach ($container->findTaggedServiceIds(self::HEALTH_CHECKER_AWARE_TAG) as $id => $attributes) {
            $service = $container->getDefinition($id);
            foreach ($healthCheckerIds as $healthCheckerId) {
                $service->addMethodCall('addHealthChecker', [new Reference($healthCheckerId)]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function replaceSessionListener(ContainerBuilder $container)
    {
        $container
            ->getDefinition('mink.listener.sessions')
            ->setClass('Oro\Bundle\TestFrameworkBundle\Behat\Listener\SessionsListener');
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processSuiteAwareSubscriber(ContainerBuilder $container)
    {
        $services = [];

        foreach ($container->findTaggedServiceIds(self::SUITE_AWARE_TAG) as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $container->getDefinition('oro_test.listener.suite_aware_subscriber')->replaceArgument(
            0,
            $services
        );
    }

    /**
     * Processes all context initializers.
     *
     * @param ContainerBuilder $container
     */
    private function processClassResolvers(ContainerBuilder $container)
    {
        $references = $this->processor->findAndSortTaggedServices($container, ContextExtension::CLASS_RESOLVER_TAG);
        $definition = $container->getDefinition('oro_test.environment.handler.feature_environment_handler');

        foreach ($references as $reference) {
            $definition->addMethodCall('registerClassResolver', array($reference));
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processBundleBehatConfigurations(ContainerBuilder $container)
    {
        /** @var KernelInterface $kernel */
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);
        $processor = new Processor();
        $configuration = new BehatBundleConfiguration($container);
        $suites = $container->getParameter('suite.configurations');
        $pages = [];
        $elements = [];
        $requiredOptionalListeners = [];

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            $configFile = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundle->getPath().self::CONFIG_PATH
            );

            if (!is_file($configFile)) {
                continue;
            }

            $config = Yaml::parse(file_get_contents($configFile));
            $processedConfiguration = $processor->processConfiguration(
                $configuration,
                $config
            );

            $this->appendConfiguration($pages, $processedConfiguration[self::PAGES_CONFIG_ROOT]);
            $this->appendConfiguration($elements, $processedConfiguration[self::ELEMENTS_CONFIG_ROOT]);
            $suites = array_merge($suites, $processedConfiguration[self::SUITES_CONFIG_ROOT]);
            $requiredOptionalListeners = array_merge(
                $requiredOptionalListeners,
                $processedConfiguration['optional_listeners']['required_for_fixtures'] ?? []
            );
        }

        $configLoader = new CumulativeConfigLoader(
            'oro_behat_isolators',
            new YamlCumulativeFileLoader('Tests/Behat/isolators.yml')
        );

        foreach (array_reverse($configLoader->load()) as $resource) {
            $loader = new YamlFileLoader($container, new FileLocator(rtrim($resource->path, 'isolators.yml')));
            $loader->load('isolators.yml');
        }

        $container->getDefinition('oro_element_factory')->replaceArgument(2, $elements);
        $container->getDefinition('oro_page_factory')->replaceArgument(1, $pages);
        $container->getDefinition('oro_behat_extension.isolation.doctrine_isolator')
            ->addMethodCall('setRequiredListeners', [array_unique($requiredOptionalListeners)]);
        $suites = array_merge($suites, $container->getParameter('suite.configurations'));
        $container->setParameter('suite.configurations', $suites);
    }

    private function appendConfiguration(array &$baseConfig, array $config)
    {
        foreach ($config as $key => $value) {
            if (array_key_exists($key, $baseConfig)) {
                throw new \InvalidArgumentException(sprintf('Configuration with "%s" key is already defined', $key));
            }

            $baseConfig[$key] = $value;
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function processReferenceRepositoryInitializers(ContainerBuilder $container)
    {
        $kernel = $container->get(Symfony2Extension::KERNEL_ID);
        $doctrineIsolator = $container->getDefinition('oro_behat_extension.isolation.doctrine_isolator');

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            $namespace = sprintf('%s\Tests\Behat\ReferenceRepositoryInitializer', $bundle->getNamespace());

            if (!class_exists($namespace)) {
                continue;
            }

            try {
                $initializer = new $namespace;
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Error while creating "%s" initializer. Initializer should not have any dependencies',
                        $namespace
                    ),
                    0,
                    $e
                );
            }

            $doctrineIsolator->addMethodCall('addInitializer', [$initializer]);
        }
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

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if (array_key_exists($bundle->getName(), $suiteConfigurations)) {
                continue;
            }

            // Add ! to the start of bundle name, because we need to get the real bundle not the inheritance
            // See OroKernel->getBundle
            $bundleSuite = $suiteGenerator->generateSuite('!'.$bundle->getName(), []);

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
