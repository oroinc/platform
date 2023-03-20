<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceWithHandlerCompilerPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class PriorityNamedTaggedServiceWithHandlerCompilerPassTest extends \PHPUnit\Framework\TestCase
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

        $compiler = new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            function (array $attributes, string $serviceId, string $tagName): array {
                return [$serviceId];
            }
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoServiceAndItIsOptional()
    {
        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);

        $compiler = new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            function (array $attributes, string $serviceId, string $tagName): array {
                return [$serviceId];
            },
            true
        );
        $compiler->process($this->container);
    }

    public function testProcessWhenNoTaggedServices()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class, [[], null]));

        $compiler = new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            function (array $attributes, string $serviceId, string $tagName): array {
                return [$serviceId];
            }
        );
        $compiler->process($this->container);

        self::assertEquals([], $service->getArgument(0));

        $serviceLocatorReference = $service->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess()
    {
        $service = $this->container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class, [[], null]));

        $this->container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME);
        $this->container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -10]);
        $this->container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 10])
            ->addTag(self::TAG_NAME, ['attr1' => 'val2'])
            ->addTag(self::TAG_NAME, ['attr1' => 'val1', 'priority' => 5]);

        $compiler = new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            self::SERVICE_ID,
            self::TAG_NAME,
            function (array $attributes, string $serviceId, string $tagName): array {
                return [
                    $serviceId,
                    $attributes['attr1'] ?? null
                ];
            }
        );
        $compiler->process($this->container);

        self::assertEquals(
            [
                ['tagged_service_3', null],
                ['tagged_service_3', 'val1'],
                ['tagged_service_1', null],
                ['tagged_service_3', 'val2'],
                ['tagged_service_2', null]
            ],
            $service->getArgument(0)
        );

        $serviceLocatorReference = $service->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'tagged_service_1' => new ServiceClosureArgument(new Reference('tagged_service_1')),
                'tagged_service_2' => new ServiceClosureArgument(new Reference('tagged_service_2')),
                'tagged_service_3' => new ServiceClosureArgument(new Reference('tagged_service_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
