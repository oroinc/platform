<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ActionLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $action = new Definition();
        $action->addTag('oro_action.action');

        $actionLocator = new Definition(ServiceLocator::class, [[]]);

        $container = new ContainerBuilder();
        $container->setDefinition('action-id', $action);
        $container->setDefinition('oro_action.action_locator', $actionLocator);

        $this->assertEquals([], $actionLocator->getArgument(0));

        $pass = new ActionLocatorPass();
        $pass->process($container);

        $this->assertEquals(
            [
                'action-id' => new Reference('action-id')
            ],
            $actionLocator->getArgument(0)
        );
    }
}
