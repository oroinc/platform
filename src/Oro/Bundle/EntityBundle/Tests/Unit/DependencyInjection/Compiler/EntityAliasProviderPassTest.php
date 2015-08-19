<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityAliasProviderPass;

class EntityAliasProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAliasProviderPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new EntityAliasProviderPass();
    }

    public function testProcessNoResolverDefinition()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(EntityAliasProviderPass::RESOLVER_SERVICE)
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $resolver  = new Definition();
        $provider1 = new Definition();
        $provider2 = new Definition();
        $provider3 = new Definition();
        $provider4 = new Definition();

        $provider1->addTag(EntityAliasProviderPass::PROVIDER_TAG_NAME, ['priority' => -100]);
        $provider2->addTag(EntityAliasProviderPass::PROVIDER_TAG_NAME, ['priority' => 100]);
        $provider3->addTag(EntityAliasProviderPass::PROVIDER_TAG_NAME);
        $provider4->addTag(EntityAliasProviderPass::PROVIDER_TAG_NAME, ['priority' => -150]);

        $container->addDefinitions(
            [
                EntityAliasProviderPass::RESOLVER_SERVICE => $resolver,
                'provider1'                               => $provider1,
                'provider2'                               => $provider2,
                'provider3'                               => $provider3,
                'provider4'                               => $provider4,
            ]
        );

        $this->compilerPass->process($container);

        $this->assertEquals(
            [
                ['addProvider', [new Reference('provider2')]],
                ['addProvider', [new Reference('provider3')]],
                ['addProvider', [new Reference('provider1')]],
                ['addProvider', [new Reference('provider4')]],
            ],
            $resolver->getMethodCalls()
        );
    }
}
