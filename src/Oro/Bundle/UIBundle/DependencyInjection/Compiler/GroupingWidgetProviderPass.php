<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class provides an algorithm to load prioritized (ksort() function is used to sort by priority)
 * and grouped widget providers for different kind of widgets.
 */
class GroupingWidgetProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    private string $serviceId;
    private string $tagName;
    private ?int $pageType;

    public function __construct(string $serviceId, string $tagName, int $pageType = null)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->pageType = $pageType;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($this->tagName, true);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [$id, $this->getAttribute($attributes, 'group')];
            }
        }
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
        }

        $serviceDef = $container->getDefinition($this->serviceId);
        $serviceDef
            ->setArgument(0, $items)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
        if (null !== $this->pageType) {
            $serviceDef->setArgument(4, $this->pageType);
        }
    }
}
