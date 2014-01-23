<?php

namespace Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmbeddedFormPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_embedded_form.manager')) {
            return;
        }
        $embeddedFormManager = $container->getDefinition('oro_embedded_form.manager');

        foreach ($container->findTaggedServiceIds('oro_embedded_form') as $id => $attributes) {
            foreach ($attributes as $eachTag) {
                $type = isset($eachTag['type']) ? $eachTag['type'] : $id;
                $label = isset($eachTag['label'])? $eachTag['label'] : $type;
                $embeddedFormManager->addMethodCall('addFormType', [$type, $label]);
            }
        }
    }

} 