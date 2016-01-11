<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\RoutingOptionsResolverPass;

class RoutingOptionsResolverPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var RoutingOptionsResolverPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new RoutingOptionsResolverPass();
    }

    public function testProcessNoResolverDefinition()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(RoutingOptionsResolverPass::CHAIN_RESOLVER_SERVICE)
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $chainResolver = new Definition();
        $resolver1     = new Definition();
        $resolver2     = new Definition();
        $resolver3     = new Definition();
        $resolver4     = new Definition();

        $resolver1->addTag(RoutingOptionsResolverPass::RESOLVER_TAG_NAME, ['priority' => -100]);
        $resolver2->addTag(RoutingOptionsResolverPass::RESOLVER_TAG_NAME, ['priority' => 100]);
        $resolver3->addTag(RoutingOptionsResolverPass::RESOLVER_TAG_NAME);
        $resolver4->addTag(RoutingOptionsResolverPass::RESOLVER_TAG_NAME, ['priority' => -100]);

        $container->addDefinitions(
            [
                RoutingOptionsResolverPass::CHAIN_RESOLVER_SERVICE => $chainResolver,
                'resolver1'                                        => $resolver1,
                'resolver2'                                        => $resolver2,
                'resolver3'                                        => $resolver3,
                'resolver4'                                        => $resolver4,
            ]
        );

        $this->compilerPass->process($container);

        $this->assertEquals(
            [
                ['addResolver', [new Reference('resolver2')]],
                ['addResolver', [new Reference('resolver3')]],
                ['addResolver', [new Reference('resolver1')]],
                ['addResolver', [new Reference('resolver4')]],
            ],
            $chainResolver->getMethodCalls()
        );
    }
}
