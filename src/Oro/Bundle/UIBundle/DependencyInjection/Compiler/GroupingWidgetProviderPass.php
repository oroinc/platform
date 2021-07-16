<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class provides an algorithm to load prioritized (ksort() function is used to sort by priority)
 * and grouped widget providers for different kind of widgets.
 */
class GroupingWidgetProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $tagName;

    /** @var int|null */
    private $pageType;

    public function __construct(string $serviceId, string $tagName, int $pageType = null)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->pageType = $pageType;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        [$services, $items] = $this->findAndInverseSortTaggedServicesWithHandler(
            $this->tagName,
            function (array $attributes, string $serviceId): array {
                return [$serviceId, $this->getAttribute($attributes, 'group')];
            },
            $container
        );

        $serviceDef = $container->getDefinition($this->serviceId);
        $serviceDef
            ->setArgument(0, $items)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
        if (null !== $this->pageType) {
            $serviceDef->setArgument(4, $this->pageType);
        }
    }
}
