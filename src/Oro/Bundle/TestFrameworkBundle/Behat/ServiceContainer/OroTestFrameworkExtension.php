<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\Tester\ServiceContainer\TesterExtension;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Nelmio\Alice\Bridge\Symfony\DependencyInjection\NelmioAliceExtension;
use Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ArtifactsHandlerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Factory;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\SessionsListener;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SymfonyBundleSuite;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Dotenv\Dotenv;
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
    private const ISOLATOR_TAG = 'oro_behat.isolator';
    private const SUITE_AWARE_TAG = 'suite_aware';
    private const HEALTH_CHECKER_TAG = 'behat_health_checker';
    private const HEALTH_CHECKER_AWARE_TAG = 'health_checker_aware';
    private const REFERENCE_REPOSITORY_INITIALIZER = 'oro_behat.reference_repository_initializer';
    private const CONFIG_PATH = '/Tests/Behat/behat.yml';
    private const ELEMENTS_CONFIG_ROOT = 'elements';
    private const PAGES_CONFIG_ROOT = 'pages';
    private const SUITES_CONFIG_ROOT = 'suites';

    private const PATH_SUFFIX = '/Features';
    private const CONTEXT_CLASS_SUFFIX = 'Tests\Behat\Context\FeatureContext';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey(): string
    {
        return 'oro_test';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        $envPath = '.env-app';
        if (is_file($envPath)) {
            (new Dotenv('ORO_ENV', 'ORO_DEBUG'))
                ->setProdEnvs(['prod', 'behat_test'])
                ->bootEnv($envPath, 'prod');
        }

        $environment = $_SERVER['ORO_ENV'] ?? $_ENV['ORO_ENV'] ?? 'prod';
        putenv('APP_ENV='.$environment);
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $environment;

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
        $this->loadBootstrap($container);

        $extension = new NelmioAliceExtension();
        $extension->load([], $container);

        $bundle = new NelmioAliceBundle();
        $bundle->build($container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $loader->load('health_checkers.yml');
        $loader->load('isolators.yml');
        $loader->load('artifacts.yml');
        $loader->load('cli_controllers.yml');
        $loader->load('kernel_services.yml');

        $this->loadSkipOnFailureStepTester($container);
        $container->setParameter('oro_test.shared_contexts', $config['shared_contexts'] ?? []);
        $container->setParameter('oro_test.artifacts.handler_configs', $config['artifacts']['handlers'] ?? []);

        // Remove reboot kernel after scenario because we have isolation in feature layer instead of scenario
        $container->removeDefinition('fob_symfony.kernel_orchestrator');

        $container->addCompilerPass(new DecoratorServicePass());
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->get(SymfonyExtension::KERNEL_ID)->registerBundles();

        $this->transferApplicationParameters($container);
        $this->processBundleBehatConfigurations($container);
        $this->resolveClassPass($container);
        $this->processBundleAutoload($container);
        $this->processReferenceRepositoryInitializers($container);
        $this->processIsolationSubscribers($container);
        $this->processSuiteAwareSubscriber($container);
        $this->processArtifactHandlers($container);
        $this->processHealthCheckers($container);
        $this->replaceSessionListener($container);
        $this->processContextInitializers($container);

        $container->get(SymfonyExtension::KERNEL_ID)->shutdown();
    }

    private function loadBootstrap(ContainerBuilder $container)
    {
        /** @var KernelInterface $kernel */
        $projectDir = $container->getParameter('paths.base');
        $bootstrapFile = $projectDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap_test.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
    }

    private function resolveClassPass(ContainerBuilder $container): void
    {
        $resolveClassPass = new ResolveClassPass();
        $resolveClassPass->process($container);
    }

    private function processContextInitializers(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition('oro_test.environment.handler.context_service_environment_handler');
        foreach ($container->findTaggedServiceIds(ContextExtension::INITIALIZER_TAG) as $serviceId => $tags) {
            $definition->addMethodCall('registerContextInitializer', [new Reference($serviceId)]);
        }
    }

    private function loadSkipOnFailureStepTester(ContainerBuilder $container): void
    {
        $definition = new Definition(SkipOnFailureStepTester::class, [
            new Reference(TesterExtension::STEP_TESTER_ID),
            new Reference('oro_test.storage.failed_features'),
        ]);
        $definition->addTag(TesterExtension::STEP_TESTER_WRAPPER_TAG);
        $container->setDefinition(TesterExtension::STEP_TESTER_WRAPPER_TAG . '.skip_on_failure', $definition);
    }

    private function transferApplicationParameters(ContainerBuilder $container): void
    {
        /** @var KernelInterface $kernel */
        $kernel = $container->get(SymfonyExtension::KERNEL_ID);
        $container->setParameter('kernel.log_dir', $kernel->getLogDir());
        $container->setParameter('kernel.project_dir', $kernel->getProjectDir());
        $container->setParameter('kernel.secret', $kernel->getContainer()->getParameter('kernel.secret'));
    }

    private function processIsolationSubscribers(ContainerBuilder $container): void
    {
        $isolators = [];

        /** @var ContainerInterface $applicationContainer */
        $applicationContainer = $container->get(SymfonyExtension::KERNEL_ID)->getContainer();

        foreach ($container->findTaggedServiceIds(self::ISOLATOR_TAG) as $id => $attributes) {
            /** @var IsolatorInterface $isolator */
            $isolator = $container->get($id);

            if ($isolator->isApplicable($applicationContainer)) {
                $reference = new Reference($id);

                foreach ($attributes as $attribute) {
                    $priority = $attribute['priority'] ?? 0;
                    $isolators[$priority][] = $reference;
                }
            }
        }

        // sort by priority and flatten
        krsort($isolators);
        $isolators = array_merge(...array_values($isolators));

        $container->getDefinition('oro_behat_extension.isolation.test_isolation_subscriber')->replaceArgument(
            0,
            $isolators
        );
    }

    private function processArtifactHandlers(ContainerBuilder $container): void
    {
        $handlerConfigurations = $container->getParameter('oro_test.artifacts.handler_configs');
        $prettySubscriberDefinition = $container->getDefinition('oro_test.artifacts.pretty_artifacts_subscriber');
        $progressSubscriberDefinition = $container->getDefinition('oro_test.artifacts.progress_artifacts_subscriber');

        foreach ($container->findTaggedServiceIds('artifacts_handler') as $id => $attributes) {
            $handlerClass = $container->getDefinition($id)->getClass();

            if (!in_array(ArtifactsHandlerInterface::class, class_implements($handlerClass), true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        '"%s" should implement "%s"',
                        $handlerClass,
                        ArtifactsHandlerInterface::class
                    )
                );
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

    private function processHealthCheckers(ContainerBuilder $container): void
    {
        $healthCheckerIds = array_keys($container->findTaggedServiceIds(self::HEALTH_CHECKER_TAG));

        foreach ($container->findTaggedServiceIds(self::HEALTH_CHECKER_AWARE_TAG) as $id => $attributes) {
            $service = $container->getDefinition($id);
            foreach ($healthCheckerIds as $healthCheckerId) {
                $service->addMethodCall('addHealthChecker', [new Reference($healthCheckerId)]);
            }
        }
    }

    private function replaceSessionListener(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('mink.listener.sessions')
            ->setClass(SessionsListener::class);
    }

    private function processSuiteAwareSubscriber(ContainerBuilder $container): void
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

    private function processBundleBehatConfigurations(ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new BehatBundleConfiguration($container);
        $suites = [$container->getParameter('suite.configurations')];
        $pages = [];
        $elements = [];
        $requiredOptionalListeners = [];

        foreach ($this->getConfigPathsPrefixes($container) as $pathPrefix) {
            $configFile = str_replace('/', DIRECTORY_SEPARATOR, $pathPrefix . self::CONFIG_PATH);
            if (!is_file($configFile)) {
                continue;
            }

            $config = Yaml::parse(file_get_contents($configFile));
            $processedConfiguration = $processor->processConfiguration($configuration, $config);

            $this->appendConfiguration($pages, $processedConfiguration[self::PAGES_CONFIG_ROOT]);
            $this->appendConfiguration($elements, $processedConfiguration[self::ELEMENTS_CONFIG_ROOT]);

            $suites[] = $processedConfiguration[self::SUITES_CONFIG_ROOT];
            $requiredOptionalListeners[] = $processedConfiguration['optional_listeners']['required_for_fixtures'] ?? [];
        }

        $suites = array_merge(...$suites);
        $requiredOptionalListeners = array_merge(...$requiredOptionalListeners);

        $configLoader = new CumulativeConfigLoader(
            'oro_behat_services',
            new NullCumulativeFileLoader('Tests/Behat/services.yml')
        );
        $resources = array_reverse($configLoader->load());
        foreach ($resources as $resource) {
            $loader = new YamlFileLoader($container, new FileLocator(rtrim($resource->path, 'services.yml')));
            $loader->load('services.yml');
        }
        $this->loadAppBehatServices($container);

        $container->getDefinition('oro_element_factory')->replaceArgument(2, $elements);
        $container->getDefinition('oro_page_factory')->replaceArgument(1, $pages);
        $container->getDefinition('oro_behat_extension.isolation.doctrine_isolator')
            ->addMethodCall('setRequiredListeners', [array_unique($requiredOptionalListeners)]);
        $suites = array_merge($suites, $container->getParameter('suite.configurations'));
        $container->setParameter('suite.configurations', $suites);
    }

    private function getConfigPathsPrefixes(ContainerBuilder $container): array
    {
        /** @var KernelInterface $kernel */
        $kernel = $container->get(SymfonyExtension::KERNEL_ID);
        $configPrefixes = [];

        foreach ($kernel->getBundles() as $bundle) {
            $configPrefixes[] = $bundle->getPath();
        }
        $configPrefixes[] = $kernel->getProjectDir() . '/src';

        return $configPrefixes;
    }

    private function loadAppBehatServices(ContainerBuilder $container): void
    {
        /** @var KernelInterface $kernel */
        $kernel = $container->get(SymfonyExtension::KERNEL_ID);

        $servicesFile = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $kernel->getProjectDir() . '/config/oro/behat_services.yml'
        );
        if (\is_file($servicesFile)) {
            $loader = new YamlFileLoader($container, new FileLocator($kernel->getProjectDir() . '/config/oro'));
            $loader->load('behat_services.yml');
        }
    }

    private function appendConfiguration(array &$baseConfig, array $config): void
    {
        foreach ($config as $key => $value) {
            if (array_key_exists($key, $baseConfig)) {
                throw new \InvalidArgumentException(sprintf('Configuration with "%s" key is already defined', $key));
            }

            $baseConfig[$key] = $value;
        }
    }

    private function processReferenceRepositoryInitializers(ContainerBuilder $container): void
    {
        $doctrineIsolator = $container->getDefinition('oro_behat_extension.isolation.doctrine_isolator');

        $referenceRepositoryInitializers = $container->findTaggedServiceIds(self::REFERENCE_REPOSITORY_INITIALIZER);
        foreach ($referenceRepositoryInitializers as $serviceId => $tags) {
            $doctrineIsolator->addMethodCall('addInitializer', [new Reference($serviceId)]);
        }
    }

    /**
     * Generate behat test suite for every bundle that registered in kernel and not configured in configuration
     */
    private function processBundleAutoload(ContainerBuilder $container): void
    {
        $suiteConfigurations = $container->getParameter('suite.configurations');
        $kernel = $container->get(SymfonyExtension::KERNEL_ID);
        $suiteGenerator = $container->get('oro_behat_extension.suite.oro_suite_generator');
        $commonContexts = $container->getParameter('oro_test.shared_contexts');

        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if (array_key_exists($bundle->getName(), $suiteConfigurations)) {
                continue;
            }

            $settings = [
                'paths' => [$bundle->getPath() . self::PATH_SUFFIX],
                'contexts' => [$bundle->getNamespace() . self::CONTEXT_CLASS_SUFFIX],
            ];

            $bundleSuite = $suiteGenerator->generateSuite($bundle->getName(), $settings);

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
     *
     * @return array
     */
    private function getSuiteContexts(SymfonyBundleSuite $bundleSuite, array $commonContexts): array
    {
        if (!$bundleSuite->hasSetting('contexts')) {
            return $commonContexts;
        }

        $suiteContexts = array_filter($bundleSuite->getSetting('contexts'), 'class_exists');

        return array_merge($suiteContexts, $commonContexts);
    }

    /**
     * @param SymfonyBundleSuite $bundleSuite
     *
     * @return bool
     */
    protected function hasValidPaths(SymfonyBundleSuite $bundleSuite): bool
    {
        if (!$bundleSuite->hasSetting('paths')) {
            return false;
        }

        return 0 < count(array_filter($bundleSuite->getSetting('paths'), 'is_dir'));
    }
}
