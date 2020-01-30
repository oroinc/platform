<?php

namespace Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all activity list providers.
 */
class ActivityListProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        list($services, $items) = $this->findAndInverseSortTaggedServicesWithHandler(
            'oro_activity_list.provider',
            function (array $attributes, string $serviceId, string $tagName): array {
                return [
                    $serviceId,
                    $this->getRequiredAttribute($attributes, 'class', $serviceId, $tagName),
                    $this->getAttribute($attributes, 'acl_class')
                ];
            },
            $container
        );

        $activityClasses = [];
        $activityAclClasses = [];
        $activityProviders = [];
        foreach ($items as list($id, $class, $aclClass)) {
            if (!isset($activityClasses[$class])) {
                $activityProviders[$class] = $services[$id];
                $activityClasses[$class] = $id;
                if ($aclClass) {
                    $activityAclClasses[$class] = $aclClass;
                }
            }
        }

        $container->getDefinition('oro_activity_list.provider.chain')
            ->setArgument(0, array_keys($activityClasses))
            ->setArgument(1, $activityAclClasses)
            ->setArgument(2, ServiceLocatorTagPass::register($container, $activityProviders));
    }
}
