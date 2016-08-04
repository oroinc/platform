<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CurrentLocalizationPass implements CompilerPassInterface
{
    const PROVIDER_TAG = 'oro_locale.extension.current_localization';
    const EXTENSION_SERVICE_ID = 'oro_locale.provider.current_localization';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXTENSION_SERVICE_ID)) {
            return;
        }

        $providers = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        if (!$providers) {
            return;
        }

        $service = $container->getDefinition(self::EXTENSION_SERVICE_ID);

        foreach ($providers as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(false);

            foreach ($attributes as $eachTag) {
                $alias = empty($eachTag['alias']) ? $id : $eachTag['alias'];

                $service->addMethodCall('addExtension', [$alias, $definition]);
            }
        }
    }
}
