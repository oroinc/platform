<?php

namespace Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available owner assignment checkers and registers them in the registry.
 */
class OwnerDeletionManagerPass implements CompilerPassInterface
{
    private const MANAGER_SERVICE  = 'oro_organization.owner_deletion_manager';
    private const CHECKER_TAG_NAME = 'oro_organization.owner_assignment_checker';
    private const ENTITY_ATTRIBUTE = 'entity';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $serviceIds = [];
        $taggedServices = $container->findTaggedServiceIds(self::CHECKER_TAG_NAME);
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
                        self::CHECKER_TAG_NAME,
                        $entityClass,
                        $serviceIds[$entityClass]
                    ));
                }
                $services[$entityClass] = new Reference($serviceId);
                $serviceIds[$entityClass] = $serviceId;
            }
        }

        $container->findDefinition(self::MANAGER_SERVICE)
            ->setArgument(0, ServiceLocatorTagPass::register($container, $services));
    }
}
