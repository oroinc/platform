<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all the resource checker related services per API request types.
 */
class ResourceCheckerRegistryPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const TAG_NAME = 'oro.api.resource_checker';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $config = [];
        $services = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $requestType = $this->getRequiredAttribute($attributes, 'requestType', $id, self::TAG_NAME);
                $resourceType = $this->getRequiredAttribute($attributes, 'resourceType', $id, self::TAG_NAME);
                $resourceChecker = $this->getRequiredAttribute($attributes, 'resourceChecker', $id, self::TAG_NAME);
                $resourceCheckerConfigProvider = $this->getRequiredAttribute(
                    $attributes,
                    'resourceCheckerConfigProvider',
                    $id,
                    self::TAG_NAME
                );
                $config[] = [$resourceType, $resourceCheckerConfigProvider, $resourceChecker, $requestType];
                $services[$resourceChecker] = new Reference($resourceChecker);
                $services[$resourceCheckerConfigProvider] = new Reference($resourceCheckerConfigProvider);
            }
        }
        $container->getDefinition('oro_api.resource_checker_registry')
            ->setArgument('$config', $config)
            ->setArgument('$container', ServiceLocatorTagPass::register($container, $services));
    }
}
