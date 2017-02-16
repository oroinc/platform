<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormTemplateDataProviderCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FormTemplateDataProviderCompilerPassTest extends TaggedServicesCompilerPassCase
{
    /** @var FormTemplateDataProviderCompilerPass */
    private $pass;

    protected function setUp()
    {
        $this->pass = new FormTemplateDataProviderCompilerPass();
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects($this->once())
            ->method('hasDefinition')->with(FormTemplateDataProviderCompilerPass::REGISTRY_SERVICE)->willReturn(true);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')->with(FormTemplateDataProviderCompilerPass::PROVIDER_TAG)
            ->willReturn(
                [
                    'tagged.service.one' => [['alias' => 'alias1']],
                    'tagged.service.two' => [['alias' => 'alias2']],
                    'tagged.service.tree' => [['aliasNotDefined']]
                ]
            );

        $serviceDefinition = $this->createMock(Definition::class);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(FormTemplateDataProviderCompilerPass::REGISTRY_SERVICE)
            ->willReturn($serviceDefinition);

        $serviceDefinition->expects($this->exactly(3))
            ->method('addMethodCall')->withConsecutive(
                ['addProviderService',['tagged.service.one', 'alias1']],
                ['addProviderService', ['tagged.service.two', 'alias2']],
                ['addProviderService', ['tagged.service.tree', 'tagged.service.tree']]
            );

        $this->pass->process($container);
    }
}
