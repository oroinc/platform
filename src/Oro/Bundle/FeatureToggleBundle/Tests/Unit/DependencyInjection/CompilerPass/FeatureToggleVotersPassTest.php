<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FeatureToggleVotersPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureToggleVotersPass
     */
    protected $featureToggleVotersPass;

    protected function setUp()
    {
        $this->featureToggleVotersPass = new FeatureToggleVotersPass();
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

        $this->featureToggleVotersPass->process($container);
    }
    
    public function testProcess()
    {
        $voters = [
            'first_voter' => [['priority' => 20]],
            'second_voter' => [['priority' => 10]],
        ];

        $expected = [
            new Reference('second_voter'),
            new Reference('first_voter')
        ];

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $featureChecker */
        $featureCheckerDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureCheckerDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('setVoters', [$expected]);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container **/
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($featureCheckerDefinition);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_featuretogle.voter')
            ->willReturn($voters);

        $this->featureToggleVotersPass->process($container);
    }
}
