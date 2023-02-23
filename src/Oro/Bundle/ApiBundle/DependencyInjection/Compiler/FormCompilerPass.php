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
 * Configures all services required for API forms.
 */
class FormCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const FORM_REGISTRY_SERVICE_ID = 'form.registry';
    private const FORM_EXTENSION_SERVICE_ID = 'form.extension';
    private const FORM_TYPE_TAG = 'form.type';
    private const FORM_TYPE_EXTENSION_TAG = 'form.type_extension';
    private const FORM_TYPE_GUESSER_TAG = 'form.type_guesser';
    private const FORM_TYPE_FACTORY_SERVICE_ID = 'form.resolved_type_factory';
    private const API_FORM_TYPE_FACTORY_SERVICE_ID = 'oro_api.form.resolved_type_factory';
    private const API_FORM_EXTENSION_STATE_SERVICE_ID = 'oro_api.form.state';
    private const API_FORM_SWITCHABLE_EXTENSION_SERVICE_ID = 'oro_api.form.switchable_extension';
    private const API_FORM_EXTENSION_SERVICE_ID = 'oro_api.form.extension';
    private const API_FORM_TYPE_TAG = 'oro.api.form.type';
    private const API_FORM_TYPE_EXTENSION_TAG = 'oro.api.form.type_extension';
    private const API_FORM_TYPE_GUESSER_TAG = 'oro.api.form.type_guesser';
    private const API_FORM_DATA_TYPE_GUESSER_SERVICE_ID = 'oro_api.form.guesser.data_type';

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContainerBuilder $container): void
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

            // load form types, form type extensions and form type guessers for API form extension
            $apiFormExtensionDef->replaceArgument(1, $this->getApiFormTypes($container, $formTypeClassNames));
            $apiFormExtensionDef->replaceArgument(2, $this->getApiFormTypeExtensions($container));
            $apiFormExtensionDef->replaceArgument(3, $this->getApiFormTypeGuessers($container));

            $serviceList = array_merge(
                $apiFormExtensionDef->getArgument(1),
                $apiFormExtensionDef->getArgument(3),
                ...array_values($apiFormExtensionDef->getArgument(2))
            );

            foreach ($serviceList as $serviceId) {
                if ($serviceId) {
                    $serviceLocatorServices[$serviceId] = new Reference($serviceId);
                }
            }

            $serviceLocator = $container->getDefinition('oro_api.form.extension_locator');
            $serviceLocator->replaceArgument(0, $serviceLocatorServices ?? []);
        }
        if ($container->hasDefinition(self::API_FORM_DATA_TYPE_GUESSER_SERVICE_ID)) {
            $dataTypeMappings = [];
            foreach ($config['form_type_guesses'] as $dataType => $value) {
                $dataTypeMappings[$dataType] = [$value['form_type'], $value['options']];
            }
            $container->getDefinition(self::API_FORM_DATA_TYPE_GUESSER_SERVICE_ID)
                ->replaceArgument(0, $dataTypeMappings);
        }
    }

    private function decorateFormTypeFactory(ContainerBuilder $container): void
    {
        $container
            ->register(self::API_FORM_TYPE_FACTORY_SERVICE_ID, ApiResolvedFormTypeFactory::class)
            ->setArguments([
                new Reference('.inner'),
                new Reference(self::API_FORM_EXTENSION_STATE_SERVICE_ID)
            ])
            ->setPublic(false)
            ->setDecoratedService(self::FORM_TYPE_FACTORY_SERVICE_ID);
    }

    private function assertExistingFormRegistry(Definition $formRegistryDef, ContainerBuilder $container): void
    {
        $formRegistryClass = $formRegistryDef->getClass();
        if (str_starts_with($formRegistryClass, '%')) {
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
        if (!\is_array($formExtensions)) {
            throw new LogicException(sprintf(
                'Cannot register API form extension because it is expected'
                . ' that the first argument of "%s" service is array. "%s" given.',
                self::FORM_REGISTRY_SERVICE_ID,
                get_debug_type($formExtensions)
            ));
        }
        if (\count($formExtensions) !== 1) {
            throw new LogicException(sprintf(
                'Cannot register API form extension because it is expected'
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

    private function addFormApiTag(
        ContainerBuilder $container,
        array $serviceIds,
        string $tagName,
        string $apiTagName
    ): void {
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

    private function getApiFormTypes(ContainerBuilder $container, array $formTypeClassNames): array
    {
        $types = array_fill_keys($formTypeClassNames, null);
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_TAG) as $id => $tags) {
            $alias = $this->getAttribute($tags[0], 'alias', $id);
            $types[$alias] = $id;
        }

        return $types;
    }

    private function getApiFormTypeExtensions(ContainerBuilder $container): array
    {
        $typeExtensions = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_EXTENSION_TAG) as $id => $tags) {
            $alias = $this->getAttribute($tags[0], 'extended_type', $id);
            $typeExtensions[$alias][] = $id;
        }

        return $typeExtensions;
    }

    private function getApiFormTypeGuessers(ContainerBuilder $container): array
    {
        $guessers = [];
        foreach ($container->findTaggedServiceIds(self::API_FORM_TYPE_GUESSER_TAG) as $id => $tags) {
            foreach ($tags as $attributes) {
                $guessers[$id] = $this->getPriorityAttribute($attributes);
            }
        }
        arsort($guessers, SORT_NUMERIC);

        return array_keys($guessers);
    }
}
