<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormTemplateDataProviderCompilerPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_form.registry.form_template_data_provider';
    const PROVIDER_TAG = 'oro_form.form_template_data_provider';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }
        $taggedServiceIds = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        if (count($taggedServiceIds) === 0) {
            return;
        }

        $service = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach ($taggedServiceIds as $id => $attributes) {
            $alias = $id;
            if (isset($attributes[0]['alias'])) {
                $alias = $attributes[0]['alias'];
            }
            $service->addMethodCall('addProviderService', [$id, $alias]);
        }
    }
}
