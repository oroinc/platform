<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\AbstractPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionPass;
use Oro\Component\Action\Tests\Unit\Action\Stub\ArrayAction as NotDispatcherAwareAction;
use Oro\Component\Action\Tests\Unit\Action\Stub\DispatcherAwareAction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ActionPassTest extends TestCase
{
    private const ACTION_FACTORY_SERVICE_ID = 'oro_action.action_factory';
    private const ACTION_TAG = 'oro_action.action';

    private AbstractPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new ActionPass();
    }

    private function assertActionServiceHasValidConfiguration(Definition $definition, bool $dispatcherAware)
    {
        self::assertFalse($definition->isShared());
        self::assertFalse($definition->isPublic());
        if ($dispatcherAware) {
            self::assertEquals(
                [
                    ['setDispatcher', [new Reference('event_dispatcher')]]
                ],
                $definition->getMethodCalls()
            );
        } else {
            self::assertEquals([], $definition->getMethodCalls());
        }
    }

    public function testProcessWithoutFactoryService(): void
    {
        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('action_service_2.class', DispatcherAwareAction::class);
        $container->register(self::ACTION_FACTORY_SERVICE_ID)
            ->setArguments([null, []]);
        $container->register('action_service_1', DispatcherAwareAction::class)
            ->addTag(self::ACTION_TAG, ['alias' => 'service_first|service_first_alias']);
        $container->register('action_service_2', '%action_service_2.class%')
            ->addTag(self::ACTION_TAG)
            ->setShared(false)
            ->setPublic(false);
        $container->register('action_service_3', NotDispatcherAwareAction::class)
            ->addTag(self::ACTION_TAG, ['alias' => 'service_third']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'service_first'       => 'action_service_1',
                'service_first_alias' => 'action_service_1',
                'action_service_2'    => 'action_service_2',
                'service_third'       => 'action_service_3'
            ],
            $container->getDefinition(self::ACTION_FACTORY_SERVICE_ID)->getArgument(1)
        );
        $this->assertActionServiceHasValidConfiguration($container->getDefinition('action_service_1'), true);
        $this->assertActionServiceHasValidConfiguration($container->getDefinition('action_service_2'), true);
        $this->assertActionServiceHasValidConfiguration($container->getDefinition('action_service_3'), false);
    }
}
