<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Trait that allows to find and sort service by priority option in the tag
 * and register these services by calling the specified method of a "chain" service.
 *
 * IMPORTANT: this trait sorts found tagged services by ksort() function,
 * as a result, the higher the priority number, the later the service are registered in a chain.
 * It is different from what Symfony proposes, so please be careful using this trait for new tags.
 * Read Symfony's "Reference Tagged Services" article and take a look at PriorityTaggedServiceTrait trait.
 * @link https://symfony.com/doc/current/service_container/tags.html#reference-tagged-services
 * @see \Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait
 *
 * @deprecated use "!tagged_iterator tag_name" for new tags
 */
trait TaggedServicesCompilerPassTrait
{
    use TaggedServiceTrait;

    /**
     * @deprecated use "!tagged_iterator tag_name" for new tags
     */
    private function registerTaggedServices(
        ContainerBuilder $container,
        string $serviceId,
        string $tagName,
        string $addMethodName
    ): void {
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $taggedServiceIds = $container->findTaggedServiceIds($tagName);
        if (count($taggedServiceIds) === 0) {
            return;
        }

        $taggedServices = [];
        foreach ($taggedServiceIds as $id => $tags) {
            $taggedServices[$this->getPriorityAttribute($tags[0])][] = [
                new Reference($id),
                $this->getAttribute($tags[0], 'alias', $id)
            ];
        }

        $taggedServices = $this->inverseSortByPriorityAndFlatten($taggedServices);

        $service = $container->getDefinition($serviceId);
        foreach ($taggedServices as $taggedService) {
            $service->addMethodCall($addMethodName, $taggedService);
        }
    }

    /**
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @return Reference[]
     *
     * @deprecated use "!tagged_iterator tag_name" for new tags
     */
    private function findAndInverseSortTaggedServices($tagName, ContainerBuilder $container)
    {
        $services = [];
        $taggedServiceIds = $container->findTaggedServiceIds($tagName, true);
        foreach ($taggedServiceIds as $id => $tags) {
            $services[$this->getPriorityAttribute($tags[0])][] = new Reference($id);
        }

        return $this->inverseSortByPriorityAndFlatten($services);
    }
}
