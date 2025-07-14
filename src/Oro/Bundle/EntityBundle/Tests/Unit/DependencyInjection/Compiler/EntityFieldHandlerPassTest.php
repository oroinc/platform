<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityFieldHandlerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityFieldHandlerPassTest extends TestCase
{
    private EntityFieldHandlerPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new EntityFieldHandlerPass();
    }

    public function testServiceNotExists(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testServiceExistsNotTaggedServices(): void
    {
        $container = new ContainerBuilder();
        $handlerDef = $container->register('oro_entity.form.entity_field.handler.processor.handler_processor');

        $this->compiler->process($container);

        self::assertSame([], $handlerDef->getMethodCalls());
    }

    public function testServiceExistsWithTaggedServices(): void
    {
        $container = new ContainerBuilder();
        $handlerDef = $container->register('oro_entity.form.entity_field.handler.processor.handler_processor');

        $container->register('handler_1')
            ->addTag('oro_entity.form.entity_field.handler');
        $container->register('handler_2')
            ->addTag('oro_entity.form.entity_field.handler');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addHandler', [new Reference('handler_1')]],
                ['addHandler', [new Reference('handler_2')]]
            ],
            $handlerDef->getMethodCalls()
        );
    }
}
