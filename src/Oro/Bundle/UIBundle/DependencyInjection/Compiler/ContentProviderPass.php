<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContentProviderPass implements CompilerPassInterface
{
    const TWIG_SERVICE_KEY                 = 'twig';
    const CONTENT_PROVIDER_TAG             = 'oro_ui.content_provider';
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
        $taggedServices                   = $container->findTaggedServiceIds(self::CONTENT_PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(false);
            }
            $isEnabled = true;
            foreach ($attributes as $attribute) {
                if (array_key_exists('enabled', $attribute)) {
                    $isEnabled = !empty($attribute['enabled']);
                    break;
                }
            }
            $contentProviderManagerDefinition->addMethodCall(
                'addContentProvider',
                array(new Reference($id), $isEnabled)
            );
        }

        if ($container->hasDefinition(self::TWIG_SERVICE_KEY)) {
            $twig = $container->getDefinition(self::TWIG_SERVICE_KEY);
            $twig->addMethodCall(
                'addGlobal',
                ['oro_ui_content_provider_manager', new Reference(self::CONTENT_PROVIDER_MANAGER_SERVICE)]
            );
        }
    }
}
