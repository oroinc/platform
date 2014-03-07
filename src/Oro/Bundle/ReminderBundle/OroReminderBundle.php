<?php

namespace Oro\Bundle\ReminderBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ReminderBundle\DependencyInjection\Compiler\AddSendProcessorCompilerPass;

class OroReminderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddSendProcessorCompilerPass());
    }
}
