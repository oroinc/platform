<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass;
use Oro\Component\DependencyInjection\ServiceLinkRegistry;
use Oro\Component\DependencyInjection\Tests\Unit\Stub\ServiceLinkRegistryAwareStub;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TaggedServiceLinkRegistryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    const TAG_NAME = 'tag_name';
    const SERVICE_NAME = 'service_name';
    const METHOD_NAME = 'methodName';
    
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var TaggedServiceLinkRegistryCompilerPass */
    private $pass;

    protected function setUp()
    {
        $this->pass = new TaggedServiceLinkRegistryCompilerPass(self::TAG_NAME, self::SERVICE_NAME, self::METHOD_NAME);
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess()
    {
        $registryDefinition = $this->createMock(Definition::class);
        $taggedFirstDefinition = $this->createMock(Definition::class);
        $taggedSecondDefinition = $this->createMock(Definition::class);
        $registryAwareServiceDefinition = $this->createMock(Definition::class);

        $this->containerBuilder->expects($this->at(0))
            ->method('hasDefinition')->with(self::SERVICE_NAME)->willReturn(true);

        $registryServiceId = 'service_name.links_registry.tag_name';

        $this->containerBuilder->expects($this->at(1))
            ->method('hasDefinition')->with($registryServiceId)->willReturn(false);

        $this->containerBuilder->expects($this->at(2))
            ->method('register')->with($registryServiceId, ServiceLinkRegistry::class)->willReturn($registryDefinition);
        $registryDefinition->expects($this->at(0))->method('setArguments')->with([new Reference('service_container')]);
        $registryDefinition->expects($this->at(1))->method('setPublic')->with(false);

        $this->containerBuilder->expects($this->at(3))
            ->method('findTaggedServiceIds')->with(self::TAG_NAME)
            ->willReturn([
                'tagged_service_one' => [['alias' => 'first']],
                'tagged_service_two' => [['no alias property']]
            ]);

        $this->containerBuilder->expects($this->at(4))
            ->method('getDefinition')->with('tagged_service_one')->willReturn($taggedFirstDefinition);
        $taggedFirstDefinition->expects($this->once())->method('setPublic')->with(true);
        $registryDefinition->expects($this->at(2))
            ->method('addMethodCall')->with('add', ['tagged_service_one', 'first']);

        $this->containerBuilder->expects($this->at(5))
            ->method('getDefinition')->with('tagged_service_two')->willReturn($taggedSecondDefinition);
        $taggedFirstDefinition->expects($this->once())->method('setPublic')->with(true);
        $registryDefinition->expects($this->at(3))
            ->method('addMethodCall')->with('add', ['tagged_service_two', 'tagged_service_two']);

        $this->containerBuilder->expects($this->at(6))->method('getDefinition')
            ->with(self::SERVICE_NAME)
            ->willReturn($registryAwareServiceDefinition);

        $registryAwareServiceDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with(self::METHOD_NAME, [new Reference($registryServiceId)]);

        $this->pass->process($this->containerBuilder);
    }

    public function testDefaultRegistryInjectionMethodCompliesInterface()
    {
        $registryDefinition = $this->createMock(Definition::class);
        $registryAwareServiceDefinition = $this->createMock(Definition::class);

        $this->containerBuilder->expects($this->at(0))
            ->method('hasDefinition')->with(self::SERVICE_NAME)->willReturn(true);

        $registryServiceId = 'service_name.links_registry.tag_name';

        $this->containerBuilder->expects($this->at(1))
            ->method('hasDefinition')->with($registryServiceId)->willReturn(false);

        $this->containerBuilder->expects($this->at(2))
            ->method('register')->with($registryServiceId, ServiceLinkRegistry::class)->willReturn($registryDefinition);
        $registryDefinition->expects($this->at(0))->method('setArguments')->with([new Reference('service_container')]);
        $registryDefinition->expects($this->at(1))->method('setPublic')->with(false);

        $this->containerBuilder->expects($this->at(3))
            ->method('findTaggedServiceIds')->with(self::TAG_NAME)
            ->willReturn([]);

        $this->containerBuilder->expects($this->at(4))->method('getDefinition')
            ->with(self::SERVICE_NAME)
            ->willReturn($registryAwareServiceDefinition);

        $registryAwareServiceDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                $this->callback(
                    function ($value) {
                        return method_exists(new ServiceLinkRegistryAwareStub(), $value);
                    }
                ),
                [new Reference($registryServiceId)]
            );

        //use default value of method
        $pass = new TaggedServiceLinkRegistryCompilerPass(self::TAG_NAME, self::SERVICE_NAME);

        $pass->process($this->containerBuilder);
    }

    public function testExceptionOnInvalidRegistryAwareServiceId()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')->with(self::SERVICE_NAME)->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service definition `service_name` not found in container.');

        $this->pass->process($this->containerBuilder);
    }

    public function testExceptionOnDoublePass()
    {
        $this->containerBuilder->expects($this->at(0))
            ->method('hasDefinition')->with(self::SERVICE_NAME)->willReturn(true);

        $registryServiceId = 'service_name.links_registry.tag_name';

        $this->containerBuilder->expects($this->at(1))
            ->method('hasDefinition')->with($registryServiceId)->willReturn(true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Only one injection of `%s` per service is currently supported.' .
                'Trying to add `%1$s` to `%2$s` service by `%3$s` tag.',
                ServiceLinkRegistry::class,
                self::SERVICE_NAME,
                self::TAG_NAME
            )
        );

        $this->pass->process($this->containerBuilder);
    }
}
