<?php

namespace Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers embedded form types with the embedded form manager.
 *
 * This pass processes all services tagged with `oro_embedded_form` and registers them
 * with the embedded form manager service. Each tagged service is added as a form type
 * with an optional type name and label, allowing the application to dynamically discover
 * and manage available embedded form types.
 */
class EmbeddedFormPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_embedded_form.manager')) {
            return;
        }
        $embeddedFormManager = $container->getDefinition('oro_embedded_form.manager');

        foreach ($container->findTaggedServiceIds('oro_embedded_form') as $id => $attributes) {
            foreach ($attributes as $eachTag) {
                $type = isset($eachTag['type']) ? $eachTag['type'] : $id;
                $label = isset($eachTag['label']) ? $eachTag['label'] : $type;
                $embeddedFormManager->addMethodCall('addFormType', [$type, $label]);
            }
        }
    }
}
