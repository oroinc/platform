<?php

namespace Oro\Bundle\FilterBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available filters tagged with the given tag and registers them in the given filter bag.
 */
class FilterTypesPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /** @var string */
    private $filterBagServiceId;

    /** @var string */
    private $filterTag;

    /**
     * @param string $filterBagServiceId
     * @param string $filterTag
     */
    public function __construct(string $filterBagServiceId, string $filterTag)
    {
        $this->filterBagServiceId = $filterBagServiceId;
        $this->filterTag = $filterTag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $filters = [];
        $taggedServices = $container->findTaggedServiceIds($this->filterTag);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $type = $this->getRequiredNotBlankAttribute(
                    $attributes,
                    'type',
                    $serviceId,
                    $this->filterTag
                );
                $filters[$type] = new Reference($serviceId);
            }
        }

        $container->findDefinition($this->filterBagServiceId)
            ->setArgument(0, array_keys($filters))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $filters));
    }
}
