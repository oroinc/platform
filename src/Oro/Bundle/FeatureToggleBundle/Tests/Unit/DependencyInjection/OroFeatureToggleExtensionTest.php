<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroFeatureToggleExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAlias()
    {
        $extension = new OroFeatureToggleExtension();

        $this->assertEquals(OroFeatureToggleExtension::ALIAS, $extension->getAlias());
    }

    public function testLoad()
    {
        $config = [
            'oro_featuretoggle' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' =>false
            ],
        ];

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $featureChecker */
        $featureCheckerDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureCheckerDefinition
            ->method('addArgument')
            ->willReturnSelf();
        $featureCheckerDefinition->expects($this->at(0))
            ->method('addArgument')
            ->with($config['oro_featuretoggle']['strategy']);
        $featureCheckerDefinition->expects($this->at(1))
            ->method('addArgument')
            ->with($config['oro_featuretoggle']['allow_if_all_abstain']);
        $featureCheckerDefinition->expects($this->at(2))
            ->method('addArgument')
            ->with($config['oro_featuretoggle']['allow_if_equal_granted_denied']);

        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($featureCheckerDefinition);

        $extension = new OroFeatureToggleExtension();
        $extension->load($config, $container);
    }
}
