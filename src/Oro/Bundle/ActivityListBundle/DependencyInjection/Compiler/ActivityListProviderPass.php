<?php

namespace Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all activity list providers.
 */
class ActivityListProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $tagName = 'oro_activity_list.provider';
        $services = [];
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName, true);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequiredAttribute($attributes, 'class', $id, $tagName),
                    $this->getAttribute($attributes, 'acl_class')
                ];
            }
        }
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
        }

        $activityClasses = [];
        $activityAclClasses = [];
        $activityProviders = [];
        foreach ($items as [$id, $class, $aclClass]) {
            if (!isset($activityClasses[$class])) {
                $activityProviders[$class] = $services[$id];
                $activityClasses[$class] = $id;
                if ($aclClass) {
                    $activityAclClasses[$class] = $aclClass;
                }
            }
        }

        $container->getDefinition('oro_activity_list.provider.chain')
            ->setArgument('$activityClasses', array_keys($activityClasses))
            ->setArgument('$activityAclClasses', $activityAclClasses)
            ->setArgument('$providerContainer', ServiceLocatorTagPass::register($container, $activityProviders));
    }
}
