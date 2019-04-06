<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all form template data providers and add them to the registry.
 */
class FormTemplateDataProviderCompilerPass implements CompilerPassInterface
{
    private const REGISTRY_SERVICE = 'oro_form.registry.form_template_data_provider';
    private const PROVIDER_TAG     = 'oro_form.form_template_data_provider';

    private const ALIAS_ATTR = 'alias';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes[self::ALIAS_ATTR])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::ALIAS_ATTR,
                        $serviceId
                    ));
                }

                $providers[$attributes[self::ALIAS_ATTR]] = new Reference($serviceId);
            }
        }

        $container->getDefinition(self::REGISTRY_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $providers));
    }
}
