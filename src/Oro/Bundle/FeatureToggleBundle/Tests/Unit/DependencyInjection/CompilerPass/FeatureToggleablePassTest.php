<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleablePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FeatureToggleablePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureToggleablePass
     */
    protected $featureToggleablePass;

    protected function setUp()
    {
        $this->featureToggleablePass = new FeatureToggleablePass();
    }
    public function testSkipProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container **/
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn(false);

        $container->expects($this->never())
            ->method('findTaggedServiceIds')
            ->with('oro_featuretogle.voter');

        $this->featureToggleablePass->process($container);
    }

    public function testProcess()
    {
        $serviceId = 'test_service';
        $featureName = 'feature';
        $services = [
            $serviceId => [['feature' => $featureName]],
        ];

        $checker = new Reference('oro_featuretoggle.checker.feature_checker');

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $featureChecker */
        $service = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service->expects($this->at(0))->method('addMethodCall')->with('addFeature', [$featureName]);
        $service->expects($this->at(1))->method('addMethodCall')->with('setFeatureChecker', [$checker]);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container **/
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap([
                [$serviceId, $service]
            ]);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_featuretogle.feature')
            ->willReturn($services);

        $this->featureToggleablePass->process($container);
    }
}
