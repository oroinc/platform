<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class provides an algorithm to load prioritized (ksort() function is used to sort by priority)
 * widget providers for different kind of widgets.
 */
class WidgetProviderPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private string $serviceId;
    private string $tagName;

    public function __construct(string $serviceId, string $tagName)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds($this->tagName);
        foreach ($taggedServices as $id => $tags) {
            $providers[$this->getPriorityAttribute($tags[0])][] = new Reference($id);
        }
        if ($providers) {
            ksort($providers);
            $providers = array_merge(...array_values($providers));
        }

        $container->getDefinition($this->serviceId)
            ->setArgument(0, new IteratorArgument($providers));
    }
}
