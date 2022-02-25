<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all content providers and adds the content provider manager to TWIG.
 */
class ContentProviderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        $items = [];
        $tagName = 'oro_ui.content_provider';
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequiredAttribute($attributes, 'alias', $id, $tagName),
                    $this->getAttribute($attributes, 'enabled', true)
                ];
            }
        }

        $services = [];
        $providers = [];
        $enabledProviders = [];
        if ($items) {
            $items = $this->sortByPriorityAndFlatten($items);
            foreach ($items as [$id, $alias, $enabled]) {
                if (!isset($services[$alias])) {
                    $services[$alias] = new Reference($id);
                    $providers[] = $alias;
                    if ($enabled) {
                        $enabledProviders[] = $alias;
                    }
                }
            }
        }

        $container->getDefinition('oro_ui.content_provider.manager')
            ->setArgument('$providerNames', $providers)
            ->setArgument('$providerContainer', ServiceLocatorTagPass::register($container, $services))
            ->setArgument('$enabledProviderNames', $enabledProviders);
    }
}
