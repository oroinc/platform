<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityAliasProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityAliasProviderPassTest extends \PHPUnit\Framework\TestCase
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
            ->with(EntityAliasProviderPass::LOADER_SERVICE)
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $loader = new Definition();

        $classProvider1 = new Definition();
        $classProvider2 = new Definition();

        $aliasProvider1 = new Definition();
        $aliasProvider2 = new Definition();
        $aliasProvider3 = new Definition();
        $aliasProvider4 = new Definition();

        $classProvider1->addTag(EntityAliasProviderPass::CLASS_PROVIDER_TAG_NAME);
        $classProvider2->addTag(EntityAliasProviderPass::CLASS_PROVIDER_TAG_NAME);

        $aliasProvider1->addTag(EntityAliasProviderPass::ALIAS_PROVIDER_TAG_NAME, ['priority' => -100]);
        $aliasProvider2->addTag(EntityAliasProviderPass::ALIAS_PROVIDER_TAG_NAME, ['priority' => 100]);
        $aliasProvider3->addTag(EntityAliasProviderPass::ALIAS_PROVIDER_TAG_NAME);
        $aliasProvider4->addTag(EntityAliasProviderPass::ALIAS_PROVIDER_TAG_NAME, ['priority' => -150]);

        $container->addDefinitions(
            [
                EntityAliasProviderPass::LOADER_SERVICE => $loader,
                'classProvider1'                        => $classProvider1,
                'classProvider2'                        => $classProvider2,
                'aliasProvider1'                        => $aliasProvider1,
                'aliasProvider2'                        => $aliasProvider2,
                'aliasProvider3'                        => $aliasProvider3,
                'aliasProvider4'                        => $aliasProvider4,
            ]
        );

        $this->compilerPass->process($container);

        $this->assertEquals(
            [
                ['addEntityClassProvider', [new Reference('classProvider1')]],
                ['addEntityClassProvider', [new Reference('classProvider2')]],
                ['addEntityAliasProvider', [new Reference('aliasProvider2')]],
                ['addEntityAliasProvider', [new Reference('aliasProvider3')]],
                ['addEntityAliasProvider', [new Reference('aliasProvider1')]],
                ['addEntityAliasProvider', [new Reference('aliasProvider4')]],
            ],
            $loader->getMethodCalls()
        );
    }
}
