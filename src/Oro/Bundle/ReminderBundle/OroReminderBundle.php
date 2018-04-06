<?php

namespace Oro\Bundle\ReminderBundle;

use Oro\Bundle\ReminderBundle\DependencyInjection\Compiler\AddSendProcessorCompilerPass;
use Oro\Bundle\ReminderBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroReminderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddSendProcessorCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
