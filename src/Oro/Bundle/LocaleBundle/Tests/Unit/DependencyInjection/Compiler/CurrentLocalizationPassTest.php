<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\CurrentLocalizationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CurrentLocalizationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    /** @var CurrentLocalizationPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new CurrentLocalizationPass();
    }

    protected function tearDown()
    {
        unset($this->containerBuilder, $this->compilerPass);
    }

    public function testProcessWithoutDefinition()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(CurrentLocalizationPass::EXTENSION_SERVICE_ID)
            ->willReturn(false);
        $this->containerBuilder->expects($this->never())->method('findTaggedServiceIds');
        $this->containerBuilder->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithoutProviders()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(CurrentLocalizationPass::EXTENSION_SERVICE_ID)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(CurrentLocalizationPass::PROVIDER_TAG)
            ->willReturn([]);
        $this->containerBuilder->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $provider1 = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $provider1->expects($this->once())->method('setPublic')->with(false);

        $provider2 = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $provider1->expects($this->once())->method('setPublic')->with(false);

        $definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addExtension', ['test.alias1', $provider1]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addExtension', ['test.provider2', $provider2]);

        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(CurrentLocalizationPass::EXTENSION_SERVICE_ID)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(CurrentLocalizationPass::PROVIDER_TAG)
            ->willReturn(
                [
                    'test.provider1' => [['alias' => 'test.alias1']],
                    'test.provider2' => [[]]
                ]
            );
        $this->containerBuilder->expects($this->exactly(3))
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [CurrentLocalizationPass::EXTENSION_SERVICE_ID, $definition],
                    ['test.provider1', $provider1],
                    ['test.provider2', $provider2]
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
