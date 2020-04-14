<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\InverseTaggedIteratorCompilerPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class InverseTaggedIteratorCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID = 'test_service';
    private const TAG_NAME   = 'test_tag';

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

        $compiler = new InverseTaggedIteratorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoServiceAndItIsOptional()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new InverseTaggedIteratorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            true
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoTaggedServices()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class, [[], null]));

        $compiler = new InverseTaggedIteratorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);

        /** @var IteratorArgument $iteratorArgument */
        $iteratorArgument = $service->getArgument(0);
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals([], $iteratorArgument->getValues());
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

        $compiler = new InverseTaggedIteratorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);

        /** @var IteratorArgument $iteratorArgument */
        $iteratorArgument = $service->getArgument(0);
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals(
            [
                new Reference('tagged_service_2'),
                new Reference('tagged_service_1'),
                new Reference('tagged_service_3')
            ],
            $iteratorArgument->getValues()
        );
    }
}
