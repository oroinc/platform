<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass\EntityAuditStrategyPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityAuditStrategyPassTest extends TestCase
{
    private EntityAuditStrategyPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new EntityAuditStrategyPass();
    }

    public function testProcessRegistryDoesNotExist()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_dataaudit.strategy_processor.entity_audit_strategy_registry');

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getMethodCalls());
    }

    public function testProcessWithTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_dataaudit.strategy_processor.entity_audit_strategy_registry');

        $container->register('service.name.1')
            ->addTag('oro_dataaudit.entity_strategy_processor', ['entityName' => 'Test\Entity1']);
        $container->register('service.name.2')
            ->addTag('oro_dataaudit.entity_strategy_processor', ['entityName' => 'Test\Entity2']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addProcessor', [new Reference('service.name.1'), 'Test\Entity1']],
                ['addProcessor', [new Reference('service.name.2'), 'Test\Entity2']],
            ],
            $registryDef->getMethodCalls()
        );
    }

    public function testProcessWithTaggedServicesWithoutEntityName()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Entity name is not set but it is required. Service: "%s", tag: "%s"',
            'service.name.3',
            'oro_dataaudit.entity_strategy_processor'
        ));

        $container = new ContainerBuilder();
        $container->register('oro_dataaudit.strategy_processor.entity_audit_strategy_registry');

        $container->register('service.name.3')
            ->addTag('oro_dataaudit.entity_strategy_processor');

        $this->compiler->process($container);
    }
}
