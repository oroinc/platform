<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFeatureToggleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroFeatureToggleExtension();
        $extension->load([], $container);

        $featureDecisionManagerDef = $container->getDefinition('oro_featuretoggle.feature_decision_manager');
        $this->assertEquals('unanimous', $featureDecisionManagerDef->getArgument('$strategy'));
        $this->assertFalse($featureDecisionManagerDef->getArgument('$allowIfAllAbstainDecisions'));
        $this->assertTrue($featureDecisionManagerDef->getArgument('$allowIfEqualGrantedDeniedDecisions'));
    }

    public function testLoadWithCustomConfigs()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $config = [
            'strategy' => 'affirmative',
            'allow_if_all_abstain' => true,
            'allow_if_equal_granted_denied' => false
        ];

        $extension = new OroFeatureToggleExtension();
        $extension->load([$config], $container);

        $featureDecisionManagerDef = $container->getDefinition('oro_featuretoggle.feature_decision_manager');
        $this->assertEquals(
            $config['strategy'],
            $featureDecisionManagerDef->getArgument('$strategy')
        );
        $this->assertEquals(
            $config['allow_if_all_abstain'],
            $featureDecisionManagerDef->getArgument('$allowIfAllAbstainDecisions')
        );
        $this->assertEquals(
            $config['allow_if_equal_granted_denied'],
            $featureDecisionManagerDef->getArgument('$allowIfEqualGrantedDeniedDecisions')
        );
    }

    public function testGetAlias()
    {
        $this->assertEquals('oro_featuretoggle', (new OroFeatureToggleExtension())->getAlias());
    }
}
