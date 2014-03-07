<?php

namespace Oro\Bundle\ReminderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddSendProcessorCompilerPass implements CompilerPassInterface
{
    const SEND_PROCESSOR_TAG = 'oro_reminder.send_processor';
    const SENDER_SERVICE = 'oro_reminder.sender';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $senderDefinition = $container->getDefinition(self::SENDER_SERVICE);
        $processors = array();
        foreach ($container->findTaggedServiceIds(self::SEND_PROCESSOR_TAG) as $id => $attributes) {
            $processors[] = new Reference($id);
        }
        $senderDefinition->replaceArgument(0, $processors);
    }
}
