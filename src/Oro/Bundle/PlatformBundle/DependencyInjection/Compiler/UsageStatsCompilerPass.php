<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass for collecting all Usage Stats providers
 */
class UsageStatsCompilerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private string $service;

    private string $tag;

    public function __construct(string $service, string $tag)
    {
        $this->service = $service;
        $this->tag = $tag;
    }

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->service)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds($this->tag);
        if (empty($taggedServices)) {
            return;
        }

        $providers = [];
        foreach ($taggedServices as $serviceId => $tags) {
            $providers[$this->getPriorityAttribute($tags[0])][] = new Reference($serviceId);
        }

        if (empty($providers)) {
            return;
        }

        $providers = $this->sortByPriorityAndFlatten($providers);

        $container
            ->getDefinition($this->service)
            ->replaceArgument(0, $providers);
    }
}
