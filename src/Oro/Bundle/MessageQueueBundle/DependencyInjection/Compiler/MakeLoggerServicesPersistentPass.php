<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\CompilerPassProviderTrait;
use Symfony\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds default logger and chanel's loggers to the list of persistent services.
 */
class MakeLoggerServicesPersistentPass extends RegisterPersistentServicesPass
{
    use CompilerPassProviderTrait;

    /**
     * {@inheritdoc}
     */
    protected function processPersistentServices(ContainerBuilder $container, array $persistentServices)
    {
        parent::processPersistentServices($container, $persistentServices);
        $container
            ->getDefinition('oro_message_queue.consumption.clear_logger_extension')
            ->replaceArgument(1, $persistentServices);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    protected function getPersistentServices(ContainerBuilder $container)
    {
        $result = [
            'logger',
            'monolog.logger.event'
        ];

        /** @var LoggerChannelPass $loggerChannelCompilerPass */
        $loggerChannelCompilerPass = $this->findCompilerPassByClassName($container, LoggerChannelPass::class);
        if (null !== $loggerChannelCompilerPass) {
            $channels = $loggerChannelCompilerPass->getChannels();
            foreach ($channels as $channel) {
                $result[] = sprintf('monolog.logger.%s', $channel);
            }
        }

        return $result;
    }
}
