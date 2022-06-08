<?php

namespace Oro\Bundle\CronBundle\DependencyInjection\Compiler;

use Oro\Bundle\CronBundle\EventListener\ConsoleCommandListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Substitutes the class for the "oro_featuretoggle.event_listener.console_command" listener
 * to not apply the "commands" configuration section for CRON commands.
 */
class ConsoleCommandListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_featuretoggle.event_listener.console_command')
            ->setClass(ConsoleCommandListener::class);
    }
}
