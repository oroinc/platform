<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Form\ApiResolvedFormTypeFactory;
use Oro\Bundle\ApiBundle\Form\SwitchableFormRegistry;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\FormRegistry;

/**
 * Configures all services required for Data API forms.
 */
class FormCompilerPass implements CompilerPassInterface
{
    private const FORM_REGISTRY_SERVICE_ID                 = 'form.registry';
    private const FORM_EXTENSION_SERVICE_ID                = 'form.extension';
    private const FORM_TYPE_TAG                            = 'form.type';
    private const FORM_TYPE_EXTENSION_TAG                  = 'form.type_extension';
    private const FORM_TYPE_GUESSER_TAG                    = 'form.type_guesser';
    private const FORM_TYPE_FACTORY_SERVICE_ID             = 'form.resolved_type_factory';
    private const API_FORM_TYPE_FACTORY_SERVICE_ID         = 'oro_api.form.resolved_type_factory';
    private const API_FORM_EXTENSION_STATE_SERVICE_ID      = 'oro_api.form.state';
    private const API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID = 'oro_api.form.switchable_extension';
    private const API_FORM_EXTENSION_SERVICE_ID            = 'oro_api.form.extension';
    private const API_FORM_TYPE_TAG                        = 'oro.api.form.type';
    private const API_FORM_TYPE_EXTENSION_TAG              = 'oro.api.form.type_extension';
    private const API_FORM_TYPE_GUESSER_TAG                = 'oro.api.form.type_guesser';
    private const API_FORM_METADATA_GUESSER_SERVICE_ID     = 'oro_api.form.guesser.metadata';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::FORM_REGISTRY_SERVICE_ID) ||
            !$container->hasDefinition(self::API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID)
        ) {
            return;
        }

        $config = DependencyInjectionUtil::getConfig($container);

        $formRegistryDef = $container->getDefinition(self::FORM_REGISTRY_SERVICE_ID);
        $this->assertExistingFormRegistry($formRegistryDef, $container);
        $formRegistryDef->setClass(SwitchableFormRegistry::class);
        $formRegistryDef->replaceArgument(0, [new Reference(self::API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID)]);
        $formRegistryDef->addArgument(new Reference(self::API_FORM_EXTENSION_STATE_SERVICE_ID));

        // decorates the "form.resolved_type_factory" service
        $this->decorateFormTypeFactory($container);

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
            $formTypeClassNames = [];
            $formTypeServiceIds = [];
            foreach ($config['form_types'] as $formType) {
                if ($container->hasDefinition($formType)) {
                    $formTypeServiceIds[] = $formType;
                } else {
                    $formTypeClassNames[] = $formType;
                }
            }
            $this->addFormApiTag(
                $container,
                $formTypeServiceIds,
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
            $apiFormExtensionDef->replaceArgument(1, $this->getApiFormTypes($container, $formTypeClassNames));
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
     * @param ContainerBuilder $container
     */
    private function decorateFormTypeFactory(ContainerBuilder $container)
    {
        $container
            ->register(self::API_FORM_TYPE_FACTORY_SERVICE_ID, ApiResolvedFormTypeFactory::class)
            ->setArguments([
                new Reference(self::API_FORM_TYPE_FACTORY_SERVICE_ID . '.inner'),
                new Reference(self::API_FORM_EXTENSION_STATE_SERVICE_ID)
            ])
            ->setPublic(false)
            ->setDecoratedService(self::FORM_TYPE_FACTORY_SERVICE_ID);
    }

    /**
     * @param Definition       $formRegistryDef
     * @param ContainerBuilder $container
     */
    private function assertExistingFormRegistry(Definition $formRegistryDef, ContainerBuilder $container)
    {
        $formRegistryClass = $formRegistryDef->getClass();
        if (0 === strpos($formRegistryClass, '%')) {
            $formRegistryClass = $container->getParameter(substr($formRegistryClass, 1, -1));
        }
        if (FormRegistry::class !== $formRegistryClass) {
            throw new LogicException(sprintf(
                'Expected class of the "%s" service is "%s", actual class is "%s".',
                self::FORM_REGISTRY_SERVICE_ID,
                FormRegistry::class,
                $formRegistryClass
            ));
        }

        $formExtensions = $formRegistryDef->getArgument(0);
        if (!is_array($formExtensions)) {
            throw new LogicException(sprintf(
                'Cannot register Data API form extension because it is expected'
                . ' that the first argument of "%s" service is array. "%s" given.',
                self::FORM_REGISTRY_SERVICE_ID,
                is_object($formExtensions) ? get_class($formExtensions) : gettype($formExtensions)
            ));
        } elseif (count($formExtensions) !== 1) {
            throw new LogicException(sprintf(
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
            ));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string[]         $serviceIds
     * @param string           $tagName
     * @param string           $apiTagName
     */
    private function addFormApiTag(ContainerBuilder $container, array $serviceIds, $tagName, $apiTagName)
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
     * @param string[]         $formTypeClassNames
     *
     * @return array
     */
    private function getApiFormTypes(ContainerBuilder $container, array $formTypeClassNames)
    {
        $types = array_fill_keys($formTypeClassNames, null);
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_TAG) as $serviceId => $tag) {
            $alias = DependencyInjectionUtil::getAttribute($tag[0], 'alias', $serviceId);
            $types[$alias] = $serviceId;
        }

        return $types;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getApiFormTypeExtensions(ContainerBuilder $container)
    {
        $typeExtensions = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_EXTENSION_TAG) as $serviceId => $tag) {
            $alias = DependencyInjectionUtil::getAttribute($tag[0], $this->getTagKeyForExtension(), $serviceId);
            $typeExtensions[$alias][] = $serviceId;
        }

        return $typeExtensions;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getApiFormTypeGuessers(ContainerBuilder $container)
    {
        $guessers = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_GUESSER_TAG) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $guessers[$serviceId] = DependencyInjectionUtil::getPriority($tag);
            }
        }
        arsort($guessers, SORT_NUMERIC);

        return array_keys($guessers);
    }

    /**
     * Provide compatibility between Symfony 2.8 and version below this
     * @return string
     */
    public function getTagKeyForExtension()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'extended_type'
            : 'alias';
    }
}
