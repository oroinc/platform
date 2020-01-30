<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all feature voters.
 */
class FeatureToggleVotersPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $voters = [];
        $taggedServices = $container->findTaggedServiceIds('oro_featuretogle.voter');
        foreach ($taggedServices as $id => $tags) {
            $voters[$this->getPriorityAttribute($tags[0])][] = new Reference($id);
        }

        $voters = $this->inverseSortByPriorityAndFlatten($voters);

        $container->getDefinition('oro_featuretoggle.checker.feature_checker')
            ->addMethodCall('setVoters', [array_values($voters)]);
    }
}
