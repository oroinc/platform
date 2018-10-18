<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Command\SynchronousCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron command that checks that consumers are alive and pushes the message if there are no available consumers.
 */
class ConsumerHeartbeatCommand extends ContainerAwareCommand implements
    CronCommandInterface,
    SynchronousCommandInterface
{
    const COMMAND_NAME = 'oro:cron:message-queue:consumer_heartbeat_check';
    const HEARTBEAT_UPDATE_PERIOD_PARAMETER_NAME = 'oro_message_queue.consumer_heartbeat_update_period';

    /** {@inheritdoc} */
    public function getDefaultDefinition()
    {
        return sprintf(
            '*/%s * * * *',
            $this->getContainer()->getParameter(self::HEARTBEAT_UPDATE_PERIOD_PARAMETER_NAME)
        );
    }

    /** {@inheritdoc} */
    public function isActive()
    {
        return true;
    }

    /** {@inheritdoc} */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Checks if there is alive consumers');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // do nothing if check was disabled with 0 config option value
        if ($this->getContainer()->getParameter(self::HEARTBEAT_UPDATE_PERIOD_PARAMETER_NAME) === 0) {
            return;
        }

        $container = $this->getContainer();
        if (!$container->get('oro_message_queue.consumption.consumer_heartbeat')->isAlive() &&
            $container->get('oro_sync.client.connection_checker')->checkConnection()
        ) {
            // Notify frontend that there are no alive consumers.
            $container->get('oro_sync.websocket_client')->publish('oro/message_queue_heartbeat', '');
        }
    }
}
