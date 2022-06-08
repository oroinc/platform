<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\ConsoleCommandListenerPass;
use Oro\Bundle\CronBundle\EventListener\ConsoleCommandListener;
use Oro\Bundle\FeatureToggleBundle\EventListener\ConsoleCommandListener as BaseListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConsoleCommandListenerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $listenerDef = $container->register('oro_featuretoggle.event_listener.console_command', BaseListener::class);

        $compiler = new ConsoleCommandListenerPass();
        $compiler->process($container);

        self::assertEquals(ConsoleCommandListener::class, $listenerDef->getClass());
    }
}
