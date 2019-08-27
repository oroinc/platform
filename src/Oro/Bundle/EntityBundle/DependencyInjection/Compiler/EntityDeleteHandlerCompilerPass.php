<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available entity delete handlers and extensions for them and registers them in the registry.
 */
class EntityDeleteHandlerCompilerPass implements CompilerPassInterface
{
    private const HANDLER_REGISTRY_SERVICE   = 'oro_entity.delete_handler_registry';
    private const HANDLER_TAG_NAME           = 'oro_entity.delete_handler';
    private const EXTENSION_REGISTRY_SERVICE = 'oro_entity.delete_handler_extension_registry';
    private const EXTENSION_TAG_NAME         = 'oro_entity.delete_handler_extension';
    private const ENTITY_ATTRIBUTE           = 'entity';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->register($container, self::HANDLER_REGISTRY_SERVICE, self::HANDLER_TAG_NAME);
        $this->register($container, self::EXTENSION_REGISTRY_SERVICE, self::EXTENSION_TAG_NAME);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $registryServiceId
     * @param string           $tagName
     */
    private function register(ContainerBuilder $container, string $registryServiceId, string $tagName): void
    {
        $services = [];
        $serviceIds = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes[self::ENTITY_ATTRIBUTE])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::ENTITY_ATTRIBUTE,
                        $serviceId
                    ));
                }
                $entityClass = $attributes[self::ENTITY_ATTRIBUTE];
                if (isset($serviceIds[$entityClass])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%1$s" must not have the tag "%2$s" and the entity "%3$s"'
                        . ' because there is another service ("%4$s") with this tag and entity.'
                        . ' Use a decoration of "%4$s" service to extend it or create a compiler pass'
                        . ' for the dependency injection container to override "%4$s" service completely.',
                        $serviceId,
                        $tagName,
                        $entityClass,
                        $serviceIds[$entityClass]
                    ));
                }
                $services[$entityClass] = new Reference($serviceId);
                $serviceIds[$entityClass] = $serviceId;
            }
        }

        $container->findDefinition($registryServiceId)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
