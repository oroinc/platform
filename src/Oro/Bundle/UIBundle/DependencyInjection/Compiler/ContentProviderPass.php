<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ContentProviderPass implements CompilerPassInterface
{
    const CONTENT_PROVIDER_TAG = 'oro_ui.content_provider';
    const CONTENT_PROVIDER_MANAGER_SERVICE = 'oro_ui.content_provider.manager';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CONTENT_PROVIDER_MANAGER_SERVICE)) {
            return;
        }

        $contentProviderManagerDefinition = $container->getDefinition(self::CONTENT_PROVIDER_MANAGER_SERVICE);
        $taggedServices = $container->findTaggedServiceIds(self::CONTENT_PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $isEnabled = true;
            foreach ($attributes as $attribute) {
                if (array_key_exists('enabled', $attribute)) {
                    $isEnabled = !empty($attribute['enabled']);
                    break;
                }
            }
            $contentProviderManagerDefinition->addMethodCall(
                'addContentProvider',
                array($id, $isEnabled)
            );
        }
    }
}
