<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroFeatureToggleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAlias()
    {
        $extension = new OroFeatureToggleExtension();

        $this->assertEquals(OroFeatureToggleExtension::ALIAS, $extension->getAlias());
    }

    public function testLoad()
    {
        /** @var Definition|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureCheckerDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureCheckerDefinition
            ->method('addArgument')
            ->willReturnSelf();
        $featureCheckerDefinition->expects($this->at(0))
            ->method('addArgument')
            ->with(FeatureChecker::STRATEGY_UNANIMOUS);
        $featureCheckerDefinition->expects($this->at(1))
            ->method('addArgument')
            ->with(false);
        $featureCheckerDefinition->expects($this->at(2))
            ->method('addArgument')
            ->with(true);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($featureCheckerDefinition);

        $extension = new OroFeatureToggleExtension();
        $extension->load([], $container);
    }
}
