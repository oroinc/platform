<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FeatureToggleVotersPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $featureCheckerDef = $container->register('oro_featuretoggle.feature_decision_manager');

        $container->register('voter_1')
            ->addTag('oro_featuretogle.voter', ['priority' => 100]);
        $container->register('voter_2')
            ->addTag('oro_featuretogle.voter');
        $container->register('voter_3')
            ->addTag('oro_featuretogle.voter', ['priority' => -100]);

        $compiler = new FeatureToggleVotersPass();
        $compiler->process($container);

        self::assertEquals(
            new IteratorArgument([new Reference('voter_3'), new Reference('voter_2'), new Reference('voter_1')]),
            $featureCheckerDef->getArgument('$voters')
        );
    }

    public function testProcessWhenNoVoters(): void
    {
        $container = new ContainerBuilder();
        $featureCheckerDef = $container->register('oro_featuretoggle.feature_decision_manager');

        $compiler = new FeatureToggleVotersPass();
        $compiler->process($container);

        self::assertEquals(new IteratorArgument([]), $featureCheckerDef->getArgument('$voters'));
    }
}
