<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\Definition;

class OroFeatureToggleExtensionTest extends ExtensionTestCase
{
    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroFeatureToggleExtension();

        $this->assertEquals(OroFeatureToggleExtension::ALIAS, $extension->getAlias());
    }

    public function testLoad()
    {
        $config = [
            'feature_checker' => [
                'strategy' => 'some_strategy',
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' =>false
            ],
        ];

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $featureChecker */
        $featureCheckerDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureCheckerDefinition->expects($this->at(0))
            ->method('addArgument')
            ->with($config['feature_checker']['strategy']);
        $featureCheckerDefinition->expects($this->at(1))
            ->method('addArgument')
            ->with($config['feature_checker']['allow_if_all_abstain']);
        $featureCheckerDefinition->expects($this->at(2))
            ->method('addArgument')
            ->with($config['feature_checker']['allow_if_equal_granted_denied']);

        $container = $this->getContainerMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($featureCheckerDefinition);

        $this->loadExtension(new OroFeatureToggleExtension(), $config);
    }
}
