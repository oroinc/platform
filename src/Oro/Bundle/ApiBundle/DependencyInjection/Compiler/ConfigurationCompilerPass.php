<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Form\SwitchableFormRegistry;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class ConfigurationCompilerPass implements CompilerPassInterface
{
    const PROCESSOR_BAG_SERVICE_ID          = 'oro_api.processor_bag';
    const ACTION_PROCESSOR_BAG_SERVICE_ID   = 'oro_api.action_processor_bag';
    const ACTION_PROCESSOR_TAG              = 'oro.api.action_processor';
    const FILTER_FACTORY_SERVICE_ID         = 'oro_api.filter_factory';
    const FILTER_FACTORY_TAG                = 'oro.api.filter_factory';
    const DEFAULT_FILTER_FACTORY_SERVICE_ID = 'oro_api.filter_factory.default';

    const FORM_REGISTRY_SERVICE_ID                 = 'form.registry';
    const EXPECTED_FORM_REGISTRY_CLASS             = 'Symfony\Component\Form\FormRegistry';
    const FORM_EXTENSION_SERVICE_ID                = 'form.extension';
    const FORM_TYPE_TAG                            = 'form.type';
    const FORM_TYPE_EXTENSION_TAG                  = 'form.type_extension';
    const FORM_TYPE_GUESSER_TAG                    = 'form.type_guesser';
    const API_FORM_REGISTRY_CLASS                  = 'Oro\Bundle\ApiBundle\Form\SwitchableFormRegistry';
    const API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID = 'oro_api.form.switchable_extension';
    const API_FORM_EXTENSION_SERVICE_ID            = 'oro_api.form.extension';
    const API_FORM_TYPE_TAG                        = 'oro.api.form.type';
    const API_FORM_TYPE_EXTENSION_TAG              = 'oro.api.form.type_extension';
    const API_FORM_TYPE_GUESSER_TAG                = 'oro.api.form.type_guesser';
    const API_FORM_METADATA_GUESSER_SERVICE_ID     = 'oro_api.form.guesser.metadata';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config = DependencyInjectionUtil::getConfig($container);

        $this->registerActionProcessors($container);
        $this->registerProcessingGroups($container, $config);
        $this->registerFilters($container, $config);
        $this->configureForms($container, $config);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerActionProcessors(ContainerBuilder $container)
    {
        $actionProcessorBagServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::ACTION_PROCESSOR_BAG_SERVICE_ID
        );
        if (null !== $actionProcessorBagServiceDef) {
            $taggedServices = $container->findTaggedServiceIds(self::ACTION_PROCESSOR_TAG);
            foreach ($taggedServices as $id => $attributes) {
                $actionProcessorBagServiceDef->addMethodCall(
                    'addProcessor',
                    [new Reference($id)]
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function registerProcessingGroups(ContainerBuilder $container, array $config)
    {
        $processorBagServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::PROCESSOR_BAG_SERVICE_ID
        );
        if (null !== $processorBagServiceDef) {
            foreach ($config['actions'] as $action => $actionConfig) {
                if (isset($actionConfig['processing_groups'])) {
                    foreach ($actionConfig['processing_groups'] as $group => $groupConfig) {
                        $processorBagServiceDef->addMethodCall(
                            'addGroup',
                            [$group, $action, $groupConfig['priority']]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function registerFilters(ContainerBuilder $container, array $config)
    {
        $filterFactoryServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::DEFAULT_FILTER_FACTORY_SERVICE_ID
        );
        if (null !== $filterFactoryServiceDef) {
            foreach ($config['filters'] as $dataType => $parameters) {
                $filterClassName = $parameters['class'];
                unset($parameters['class']);
                $filterFactoryServiceDef->addMethodCall(
                    'addFilter',
                    [$dataType, $filterClassName, $parameters]
                );
            }
        }
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::FILTER_FACTORY_SERVICE_ID,
            self::FILTER_FACTORY_TAG,
            'addFilterFactory'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function configureForms(ContainerBuilder $container, array $config)
    {
        if (!$container->hasDefinition(self::FORM_REGISTRY_SERVICE_ID) ||
            !$container->hasDefinition(self::API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID)
        ) {
            return;
        }

        $formRegistryDef = $container->getDefinition(self::FORM_REGISTRY_SERVICE_ID);
        $this->assertExistingFormRegistry($formRegistryDef, $container);
        $formRegistryDef->setClass(self::API_FORM_REGISTRY_CLASS);
        $formRegistryDef->replaceArgument(0, [new Reference(self::API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID)]);

        $apiFormDef = $container->getDefinition(self::API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID);
        if ($container->hasDefinition(self::FORM_EXTENSION_SERVICE_ID)) {
            $container->getDefinition(self::FORM_EXTENSION_SERVICE_ID)->setPublic(true);
            $apiFormDef->addMethodCall(
                'addExtension',
                [SwitchableFormRegistry::DEFAULT_EXTENSION, self::FORM_EXTENSION_SERVICE_ID]
            );
        }
        if ($container->hasDefinition(self::API_FORM_EXTENSION_SERVICE_ID)) {
            $apiFormExtensionDef = $container->getDefinition(self::API_FORM_EXTENSION_SERVICE_ID);
            $apiFormExtensionDef->setPublic(true);
            $apiFormDef->addMethodCall(
                'addExtension',
                [SwitchableFormRegistry::API_EXTENSION, self::API_FORM_EXTENSION_SERVICE_ID]
            );

            // reuse existing form types, form type extensions and form type guessers
            $this->addFormApiTag(
                $container,
                $config['form_types'],
                self::FORM_TYPE_TAG,
                self::API_FORM_TYPE_TAG
            );
            $this->addFormApiTag(
                $container,
                $config['form_type_extensions'],
                self::FORM_TYPE_EXTENSION_TAG,
                self::API_FORM_TYPE_EXTENSION_TAG
            );
            $this->addFormApiTag(
                $container,
                $config['form_type_guessers'],
                self::FORM_TYPE_GUESSER_TAG,
                self::API_FORM_TYPE_GUESSER_TAG
            );

            // load form types, form type extensions and form type guessers for Data API form extension
            $apiFormExtensionDef->replaceArgument(1, $this->getApiFormTypes($container));
            $apiFormExtensionDef->replaceArgument(2, $this->getApiFormTypeExtensions($container));
            $apiFormExtensionDef->replaceArgument(3, $this->getApiFormTypeGuessers($container));
        }
        if ($container->hasDefinition(self::API_FORM_METADATA_GUESSER_SERVICE_ID)) {
            $dataTypeMappings = [];
            foreach ($config['form_type_guesses'] as $dataType => $value) {
                $dataTypeMappings[$dataType] = [$value['form_type'], $value['options']];
            }
            $container->getDefinition(self::API_FORM_METADATA_GUESSER_SERVICE_ID)
                ->replaceArgument(0, $dataTypeMappings);
        }
    }

    /**
     * @param Definition       $formRegistryDef
     * @param ContainerBuilder $container
     */
    protected function assertExistingFormRegistry(Definition $formRegistryDef, ContainerBuilder $container)
    {
        $formRegistryClass = $formRegistryDef->getClass();
        if (0 === strpos($formRegistryClass, '%')) {
            $formRegistryClass = $container->getParameter(substr($formRegistryClass, 1, -1));
        }
        if (self::EXPECTED_FORM_REGISTRY_CLASS !== $formRegistryClass) {
            throw new LogicException(
                sprintf(
                    'Expected class of the "%s" service is "%s", actual class is "%s".',
                    self::FORM_REGISTRY_SERVICE_ID,
                    self::EXPECTED_FORM_REGISTRY_CLASS,
                    $formRegistryClass
                )
            );
        }

        $formExtensions = $formRegistryDef->getArgument(0);
        if (!is_array($formExtensions)) {
            throw new LogicException(
                sprintf(
                    'Cannot register Data API form extension because it is expected'
                    . ' that the first argument of "%s" service is array. "%s" given.',
                    self::FORM_REGISTRY_SERVICE_ID,
                    is_object($formExtensions) ? get_class($formExtensions) : gettype($formExtensions)
                )
            );
        } elseif (count($formExtensions) !== 1) {
            throw new LogicException(
                sprintf(
                    'Cannot register Data API form extension because it is expected'
                    . ' that the first argument of "%s" service is array contains only one element.'
                    . ' Detected the following form extension: %s.',
                    self::FORM_REGISTRY_SERVICE_ID,
                    implode(
                        ', ',
                        array_map(
                            function (Reference $ref) {
                                return (string)$ref;
                            },
                            $formExtensions
                        )
                    )
                )
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string[]         $serviceIds
     * @param string           $tagName
     * @param string           $apiTagName
     */
    protected function addFormApiTag(ContainerBuilder $container, array $serviceIds, $tagName, $apiTagName)
    {
        foreach ($serviceIds as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $definition = $container->getDefinition($serviceId);
                $tags = $definition->getTag($tagName);
                foreach ($tags as $tag) {
                    $definition->addTag($apiTagName, $tag);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getApiFormTypes(ContainerBuilder $container)
    {
        $types = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_TAG) as $serviceId => $tag) {
            $alias = isset($tag[0]['alias'])
                ? $tag[0]['alias']
                : $serviceId;
            $types[$alias] = $serviceId;
        }

        return $types;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getApiFormTypeExtensions(ContainerBuilder $container)
    {
        $typeExtensions = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_EXTENSION_TAG) as $serviceId => $tag) {
            $alias = isset($tag[0]['alias'])
                ? $tag[0]['alias']
                : $serviceId;
            $typeExtensions[$alias][] = $serviceId;
        }

        return $typeExtensions;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function getApiFormTypeGuessers(ContainerBuilder $container)
    {
        $guessers = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_GUESSER_TAG) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $guessers[$serviceId] = !empty($tag['priority']) ? $tag['priority'] : 0;
            }
        }
        arsort($guessers, SORT_NUMERIC);

        return array_keys($guessers);
    }
}
