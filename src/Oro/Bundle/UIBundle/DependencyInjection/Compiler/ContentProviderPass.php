<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all content providers in the content provider manager.
 */
class ContentProviderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private string $contentProviderManagerId;

    private string $contentProviderTagName;

    public function __construct(string $contentProviderManagerId, string $contentProviderTagName)
    {
        $this->contentProviderManagerId = $contentProviderManagerId;
        $this->contentProviderTagName = $contentProviderTagName;
    }

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($this->contentProviderTagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequiredAttribute($attributes, 'alias', $id, $this->contentProviderTagName),
                    $this->getAttribute($attributes, 'enabled', true),
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

        $container->getDefinition($this->contentProviderManagerId)
            ->setArgument('$providerNames', $providers)
            ->setArgument('$providerContainer', ServiceLocatorTagPass::register($container, $services))
            ->setArgument('$enabledProviderNames', $enabledProviders);
    }
}
