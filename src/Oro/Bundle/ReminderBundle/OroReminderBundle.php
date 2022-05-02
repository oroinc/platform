<?php

namespace Oro\Bundle\ReminderBundle;

use Oro\Bundle\ReminderBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroReminderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_reminder.send_processor_registry',
            'oro_reminder.send_processor',
            'method'
        ));
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
