<?php

namespace Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds all available owner assignment checkers and registers them in the registry.
 */
class OwnerDeletionManagerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const MANAGER_SERVICE  = 'oro_organization.owner_deletion_manager';
    private const CHECKER_TAG_NAME = 'oro_organization.owner_assignment_checker';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $serviceIds = [];
        $taggedServices = $container->findTaggedServiceIds(self::CHECKER_TAG_NAME);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $entityClass = $this->getRequiredAttribute($attributes, 'entity', $id, self::CHECKER_TAG_NAME);
                if (isset($serviceIds[$entityClass])) {
                    throw new InvalidArgumentException(sprintf(
                        'The service "%1$s" must not have the tag "%2$s" and the entity "%3$s"'
                        . ' because there is another service ("%4$s") with this tag and entity.'
                        . ' Use a decoration of "%4$s" service to extend it or create a compiler pass'
                        . ' for the dependency injection container to override "%4$s" service completely.',
                        $id,
                        self::CHECKER_TAG_NAME,
                        $entityClass,
                        $serviceIds[$entityClass]
                    ));
                }
                $services[$entityClass] = new Reference($id);
                $serviceIds[$entityClass] = $id;
            }
        }

        $container->findDefinition(self::MANAGER_SERVICE)
            ->setArgument('$checkerContainer', ServiceLocatorTagPass::register($container, $services));
    }
}
