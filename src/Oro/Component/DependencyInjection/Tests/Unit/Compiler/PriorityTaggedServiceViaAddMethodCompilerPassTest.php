<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedServiceViaAddMethodCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PriorityTaggedServiceViaAddMethodCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID      = 'test_service';
    private const TAG_NAME        = 'test_tag';
    private const ADD_METHOD_NAME = 'addTaggedService';

    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testProcessWhenNoServiceAndItIsRequired()
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new PriorityTaggedServiceViaAddMethodCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            self::ADD_METHOD_NAME
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoServiceAndItIsOptional()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new PriorityTaggedServiceViaAddMethodCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            self::ADD_METHOD_NAME,
            true
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoTaggedServices()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $compiler = new PriorityTaggedServiceViaAddMethodCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            self::ADD_METHOD_NAME
        );
        $compiler->process($this->container);

        self::assertCount(0, $service->getMethodCalls());
    }

    public function testProcess()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -10]);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 10]);

        $compiler = new PriorityTaggedServiceViaAddMethodCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            self::ADD_METHOD_NAME
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                [self::ADD_METHOD_NAME, [new Reference('tagged_service_3')]],
                [self::ADD_METHOD_NAME, [new Reference('tagged_service_1')]],
                [self::ADD_METHOD_NAME, [new Reference('tagged_service_2')]]
            ],
            $service->getMethodCalls()
        );
    }
}
