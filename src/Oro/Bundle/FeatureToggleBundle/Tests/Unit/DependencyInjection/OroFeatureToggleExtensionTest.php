<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFeatureToggleExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroFeatureToggleExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroFeatureToggleExtension();
    }

    public function testGetAlias()
    {
        $this->assertEquals('oro_featuretoggle', $this->extension->getAlias());
    }

    public function testLoadWithoutConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->extension->load([], $container);

        $featureCheckerDef = $container->getDefinition('oro_featuretoggle.checker.feature_checker');
        $this->assertEquals(FeatureChecker::STRATEGY_UNANIMOUS, $featureCheckerDef->getArgument('$strategy'));
        $this->assertFalse($featureCheckerDef->getArgument('$allowIfAllAbstainDecisions'));
        $this->assertTrue($featureCheckerDef->getArgument('$allowIfEqualGrantedDeniedDecisions'));
    }

    public function testLoadWithConfig()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $config = [
            'strategy'                      => FeatureChecker::STRATEGY_AFFIRMATIVE,
            'allow_if_all_abstain'          => true,
            'allow_if_equal_granted_denied' => false
        ];

        $this->extension->load([$config], $container);

        $featureCheckerDef = $container->getDefinition('oro_featuretoggle.checker.feature_checker');
        $this->assertEquals(
            $config['strategy'],
            $featureCheckerDef->getArgument('$strategy')
        );
        $this->assertEquals(
            $config['allow_if_all_abstain'],
            $featureCheckerDef->getArgument('$allowIfAllAbstainDecisions')
        );
        $this->assertEquals(
            $config['allow_if_equal_granted_denied'],
            $featureCheckerDef->getArgument('$allowIfEqualGrantedDeniedDecisions')
        );
    }
}
