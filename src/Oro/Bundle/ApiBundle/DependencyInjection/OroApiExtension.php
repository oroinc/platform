<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class OroApiExtension extends Extension implements PrependExtensionInterface
{
    public const API_DOC_PATH_PARAMETER_NAME  = 'oro_api.api_doc.path';
    public const API_DOC_VIEWS_PARAMETER_NAME = 'oro_api.api_doc.views';

    private const ACTION_PROCESSOR_BAG_SERVICE_ID      = 'oro_api.action_processor_bag';
    private const CONFIG_EXTENSION_REGISTRY_SERVICE_ID = 'oro_api.config_extension_registry';

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
        $loader->load('form.yml');
        $loader->load('processors.normalize_value.yml');
        $loader->load('processors.collect_resources.yml');
        $loader->load('processors.collect_subresources.yml');
        $loader->load('processors.get_config.yml');
        $loader->load('processors.get_metadata.yml');
        $loader->load('processors.customize_loaded_data.yml');
        $loader->load('processors.shared.yml');
        $loader->load('processors.get_list.yml');
        $loader->load('processors.get.yml');
        $loader->load('processors.delete.yml');
        $loader->load('processors.delete_list.yml');
        $loader->load('processors.create.yml');
        $loader->load('processors.update.yml');
        $loader->load('processors.get_subresource.yml');
        $loader->load('processors.get_relationship.yml');
        $loader->load('processors.delete_relationship.yml');
        $loader->load('processors.add_relationship.yml');
        $loader->load('processors.update_relationship.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }

        /**
         * To load configuration we need fully configured config tree builder,
         * that's why the action processors bag and all configuration extensions should be registered before.
         */
        $this->registerConfigParameters($container, $config);
        $this->registerActionProcessors($container, $config);
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

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $configs = $container->getExtensionConfig('fos_rest');
            foreach ($configs as $key => $config) {
                if (isset($config['format_listener']['rules']) && is_array($config['format_listener']['rules'])) {
                    // add REST API format listener rule
                    array_unshift(
                        $configs[$key]['format_listener']['rules'],
                        [
                            'path'             => '^/api/(?!(rest|doc)(/|$)+)',
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
                ->setDefinition(
                    $configBagDecoratorServiceId,
                    new Definition(
                        'Oro\Bundle\ApiBundle\Tests\Functional\Environment\TestConfigBag',
                        [
                            new Reference($configBagDecoratorServiceId . '.inner'),
                            new Reference('oro_api.config_merger.entity')
                        ]
                    )
                )
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
        $container->setParameter(self::API_DOC_PATH_PARAMETER_NAME, $config['documentation_path']);
        $container->setParameter(self::API_DOC_VIEWS_PARAMETER_NAME, array_keys($config['api_doc_views']));
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
                        ->setDefinition(
                            $actionProcessorDecoratorServiceId,
                            new Definition(
                                'Oro\Component\ChainProcessor\Debug\TraceableActionProcessor',
                                [
                                    new Reference($actionProcessorDecoratorServiceId . '.inner'),
                                    new Reference('oro_api.profiler.logger')
                                ]
                            )
                        )
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
}
