<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\ServiceLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

class ServiceLocatorCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID = 'test_service';
    private const TAG_NAME = 'test_tag';

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testProcessWhenNoServiceAndItIsRequired()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoServiceAndItIsOptional()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            null,
            true
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoTaggedServices()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);

        self::assertEquals([], $service->getArgument(0));
    }

    public function testProcessWithoutNameAttribute()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -10]);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item3', 'priority' => 10]);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                'tagged_service_1' => new Reference('tagged_service_1'),
                'tagged_service_2' => new Reference('tagged_service_2'),
                'tagged_service_3' => new Reference('tagged_service_3')
            ],
            $service->getArgument(0)
        );
    }

    public function testProcessWithNameAttribute()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -10]);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item3', 'priority' => 10]);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                'tagged_service_1' => new Reference('tagged_service_1'),
                'tagged_service_2' => new Reference('tagged_service_2'),
                'item3'            => new Reference('tagged_service_3')
            ],
            $service->getArgument(0)
        );
    }

    public function testProcessOverrideByName()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2']);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'item2', 'priority' => -10]);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                'item2' => new Reference('tagged_service_1')
            ],
            $service->getArgument(0)
        );
    }

    public function testProcessOverrideByServiceId()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['alias' => 'tagged_service_1', 'priority' => 10]);

        $compiler = new ServiceLocatorCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            'alias'
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                'tagged_service_1' => new Reference('tagged_service_2')
            ],
            $service->getArgument(0)
        );
    }
}
