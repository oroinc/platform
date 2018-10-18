<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestConfigBag;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\Debug\TraceableActionProcessor;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroApiExtension extends Extension implements PrependExtensionInterface
{
    public const API_DOC_VIEWS_PARAMETER_NAME        = 'oro_api.api_doc.views';
    public const API_DOC_DEFAULT_VIEW_PARAMETER_NAME = 'oro_api.api_doc.default_view';
    public const REST_API_PREFIX_PARAMETER_NAME      = 'oro_api.rest.prefix';
    public const REST_API_PATTERN_PARAMETER_NAME     = 'oro_api.rest.pattern';

    private const REST_API_PREFIX_CONFIG  = 'rest_api_prefix';
    private const REST_API_PATTERN_CONFIG = 'rest_api_pattern';

    private const ACTION_PROCESSOR_BAG_SERVICE_ID               = 'oro_api.action_processor_bag';
    private const CONFIG_EXTENSION_REGISTRY_SERVICE_ID          = 'oro_api.config_extension_registry';
    private const FILTER_OPERATOR_REGISTRY_SERVICE_ID           = 'oro_api.filter_operator_registry';
    private const REST_FILTER_VALUE_ACCESSOR_FACTORY_SERVICE_ID = 'oro_api.rest.filter_value_accessor_factory';
    private const CACHE_CONTROL_PROCESSOR_SERVICE_ID            = 'oro_api.options.rest.set_cache_control';
    private const MAX_AGE_PROCESSOR_SERVICE_ID                  = 'oro_api.options.rest.cors.set_max_age';
    private const ALLOW_ORIGIN_PROCESSOR_SERVICE_ID             = 'oro_api.rest.cors.set_allow_origin';
    private const CORS_HEADERS_PROCESSOR_SERVICE_ID             = 'oro_api.rest.cors.set_allow_and_expose_headers';
    private const CONFIG_CACHE_WARMER_SERVICE_ID                = 'oro_api.config_cache_warmer';
    private const CACHE_MANAGER_SERVICE_ID                      = 'oro_api.cache_manager';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        // remember the configuration to be able to use it in compiler passes
        DependencyInjectionUtil::setConfig($container, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('data_transformers.yml');
        $loader->load('filters.yml');
        $loader->load('form.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.collect_resources.yml');
        $loader->load('processors.collect_subresources.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.customize_loaded_data.yml');
        $loader->load('processors.shared.yml');
        $loader->load('processors.options.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');
        $loader->load('processors.delete.yml');
        $loader->load('processors.delete_list.yml');
        $loader->load('processors.create.yml');
        $loader->load('processors.update.yml');
        $loader->load('processors.get_subresource.yml');
        $loader->load('processors.change_subresource.yml');
        $loader->load('processors.get_relationship.yml');
        $loader->load('processors.delete_relationship.yml');
        $loader->load('processors.add_relationship.yml');
        $loader->load('processors.update_relationship.yml');
        $loader->load('commands.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }

        /**
         * To load configuration we need fully configured config tree builder,
         * that's why the action processors bag and all configuration extensions should be registered before.
         */
        $this->registerConfigParameters($container, $config);
        $this->registerActionProcessors($container, $config);
        $this->registerFilterOperators($container, $config);
        $this->registerConfigExtensions($container, $config);

        try {
            $this->loadApiConfiguration($container, $config);
        } catch (InvalidConfigurationException $e) {
            // we have to rethrow the configuration exception but without an inner exception,
            // otherwise a message of the root exception is displayed
            if (null !== $e->getPrevious()) {
                $e = new InvalidConfigurationException($e->getMessage());
            }
            throw $e;
        }

        $this->configureCors($container, $config);

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        // set "oro_api.rest.prefix" and "oro_api.rest.pattern" parameters
        // they are required to correct processing a configuration of FOSRestBundle and SecurityBundle
        $configs = $container->getExtensionConfig($this->getAlias());
        $filteredConfigs = [];
        foreach ($configs as $item) {
            $filteredItem = [];
            if (\array_key_exists(self::REST_API_PREFIX_CONFIG, $item)) {
                $filteredItem[self::REST_API_PREFIX_CONFIG] = $item[self::REST_API_PREFIX_CONFIG];
            }
            if (\array_key_exists(self::REST_API_PATTERN_CONFIG, $item)) {
                $filteredItem[self::REST_API_PATTERN_CONFIG] = $item[self::REST_API_PATTERN_CONFIG];
            }
            if (!empty($filteredItem)) {
                $filteredConfigs[] = $filteredItem;
            }
        }
        $config = $this->processConfiguration($this->getConfiguration($filteredConfigs, $container), $filteredConfigs);
        $container->setParameter(self::REST_API_PREFIX_PARAMETER_NAME, $config[self::REST_API_PREFIX_CONFIG]);
        $container->setParameter(self::REST_API_PATTERN_PARAMETER_NAME, $config[self::REST_API_PATTERN_CONFIG]);

        if ($container instanceof ExtendedContainerBuilder) {
            $configs = $container->getExtensionConfig('fos_rest');
            foreach ($configs as $key => $config) {
                if (isset($config['format_listener']['rules']) && is_array($config['format_listener']['rules'])) {
                    // add REST API format listener rule
                    array_unshift(
                        $configs[$key]['format_listener']['rules'],
                        [
                            'path'             => '%' . self::REST_API_PATTERN_PARAMETER_NAME . '%',
                            'priorities'       => ['json'],
                            'fallback_format'  => 'json',
                            'prefer_extension' => false
                        ]
                    );
                    break;
                }
            }
            $container->setExtensionConfig('fos_rest', $configs);
        }

        if ('test' === $container->getParameter('kernel.environment')) {
            $fileLocator = new FileLocator(__DIR__ . '/../Tests/Functional/Environment');
            $configData = Yaml::parse(file_get_contents($fileLocator->locate('app.yml')));
            foreach ($configData as $name => $config) {
                $container->prependExtensionConfig($name, $config);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureTestEnvironment(ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Tests/Functional/Environment')
        );
        $loader->load('services.yml');

        // oro_api.tests.config_bag.*
        $configBags = $container->getDefinition('oro_api.config_bag_registry')->getArgument(0);
        foreach ($configBags as $configBag) {
            $configBagServiceId = $configBag[0];
            $configBagDecoratorServiceId = str_replace('oro_api.', 'oro_api.tests.', $configBagServiceId);
            $container
                ->register($configBagDecoratorServiceId, TestConfigBag::class)
                ->setArguments([
                    new Reference($configBagDecoratorServiceId . '.inner'),
                    new Reference('oro_api.config_merger.entity')
                ])
                ->setDecoratedService($configBagServiceId, null, 255)
                ->setPublic(false);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function loadApiConfiguration(ContainerBuilder $container, array $config)
    {
        $loader = new ConfigurationLoader($container);
        $loader->load($config);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function registerConfigParameters(ContainerBuilder $container, array $config)
    {
        $container
            ->getDefinition(self::CONFIG_EXTENSION_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $config['config_max_nesting_level']);

        $apiDocViews = $config['api_doc_views'];
        $container->setParameter(self::API_DOC_VIEWS_PARAMETER_NAME, array_keys($apiDocViews));
        $container->setParameter(self::API_DOC_DEFAULT_VIEW_PARAMETER_NAME, $this->getDefaultView($apiDocViews));

        $configFiles = [];
        $cacheManagerConfigKeys = [];
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            $configFiles[$configKey] = $fileConfig['file_name'];
            $cacheManagerConfigKeys[$configKey] = $fileConfig['request_type'] ?? [];
        }
        $cacheManagerApiDocViews = [];
        foreach ($apiDocViews as $view => $viewConfig) {
            $cacheManagerApiDocViews[$view] = $viewConfig['request_type'] ?? [];
        }

        $container
            ->getDefinition(self::CONFIG_CACHE_WARMER_SERVICE_ID)
            ->replaceArgument(0, $configFiles);
        $container
            ->getDefinition(self::CACHE_MANAGER_SERVICE_ID)
            ->replaceArgument(0, $cacheManagerConfigKeys)
            ->replaceArgument(1, $cacheManagerApiDocViews);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function registerActionProcessors(ContainerBuilder $container, array $config)
    {
        $actionProcessorBagServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::ACTION_PROCESSOR_BAG_SERVICE_ID
        );
        if (null !== $actionProcessorBagServiceDef) {
            $debug = $container->getParameter('kernel.debug');
            $logger = new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
            foreach ($config['actions'] as $action => $actionConfig) {
                if (empty($actionConfig['processor_service_id'])) {
                    continue;
                }
                $actionProcessorServiceId = $actionConfig['processor_service_id'];
                // inject the logger for "api" channel into an action processor
                // we have to do it in this way rather than in service.yml to avoid
                // "The service definition "logger" does not exist." exception
                $container->getDefinition($actionProcessorServiceId)
                    ->addTag('monolog.logger', ['channel' => 'api'])
                    ->addMethodCall('setLogger', [$logger]);
                // register an action processor in the bag
                $actionProcessorBagServiceDef->addMethodCall(
                    'addProcessor',
                    [new Reference($actionProcessorServiceId)]
                );

                // decorate with TraceableActionProcessor
                if ($debug) {
                    $actionProcessorDecoratorServiceId = $actionProcessorServiceId . '.oro_api.profiler';
                    $container
                        ->register($actionProcessorDecoratorServiceId, TraceableActionProcessor::class)
                        ->setArguments([
                            new Reference($actionProcessorDecoratorServiceId . '.inner'),
                            new Reference('oro_api.profiler.logger')
                        ])
                        // should be at the top of the decoration chain
                        ->setDecoratedService($actionProcessorServiceId, null, -255)
                        ->setPublic(false);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function registerFilterOperators(ContainerBuilder $container, array $config)
    {
        $filterOperatorRegistryDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::FILTER_OPERATOR_REGISTRY_SERVICE_ID
        );
        if (null !== $filterOperatorRegistryDef) {
            $filterOperatorRegistryDef->replaceArgument(0, $config['filter_operators']);
        }
        $restFilterValueAccessorFactoryDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::REST_FILTER_VALUE_ACCESSOR_FACTORY_SERVICE_ID
        );
        if (null !== $restFilterValueAccessorFactoryDef) {
            $restFilterValueAccessorFactoryDef->replaceArgument(1, $config['filter_operators']);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function registerConfigExtensions(ContainerBuilder $container, array $config)
    {
        $configExtensionRegistryDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::CONFIG_EXTENSION_REGISTRY_SERVICE_ID
        );
        if (null !== $configExtensionRegistryDef) {
            foreach ($config['config_extensions'] as $serviceId) {
                $configExtensionRegistryDef->addMethodCall(
                    'addExtension',
                    [new Reference($serviceId)]
                );
            }
        }
    }

    /**
     * @param array $views
     *
     * @return string|null
     */
    private function getDefaultView(array $views): ?string
    {
        $defaultView = null;
        foreach ($views as $name => $view) {
            if (\array_key_exists('default', $view) && $view['default']) {
                $defaultView = $name;
                break;
            }
        }

        return $defaultView;
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureCors(ContainerBuilder $container, array $config)
    {
        $corsConfig = $config['cors'];
        $container->getDefinition(self::CACHE_CONTROL_PROCESSOR_SERVICE_ID)
            ->replaceArgument(0, $corsConfig['preflight_max_age']);
        $container->getDefinition(self::MAX_AGE_PROCESSOR_SERVICE_ID)
            ->replaceArgument(0, $corsConfig['preflight_max_age']);
        $container->getDefinition(self::ALLOW_ORIGIN_PROCESSOR_SERVICE_ID)
            ->replaceArgument(0, $corsConfig['allow_origins']);
        $container->getDefinition(self::CORS_HEADERS_PROCESSOR_SERVICE_ID)
            ->replaceArgument(0, $corsConfig['allow_headers'])
            ->replaceArgument(1, $corsConfig['expose_headers'])
            ->replaceArgument(2, $corsConfig['allow_credentials']);
    }
}
