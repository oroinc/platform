<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Command\SynchronousCommandInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
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
    const HEARTBEAT_UPDATE_PERIOD_PARAMETER_NAME = 'oro_message_queue.consumer_heartbeat_update_period';

    /** @var string */
    protected static $defaultName = 'oro:cron:message-queue:consumer_heartbeat_check';

    /** @var ConsumerHeartbeat */
    private $consumerHeartbeat;

    /** var ConnectionChecker **/
    private $connectionChecker;

    /** var WebsocketClientInterface **/
    private $websocketClient;

    /**
     * @param ConsumerHeartbeat $consumerHeartbeat
     * @param ConnectionChecker $connectionChecker
     * @param WebsocketClientInterface $websocketClient
     */
    public function __construct(
        ConsumerHeartbeat $consumerHeartbeat,
        ConnectionChecker $connectionChecker,
        WebsocketClientInterface $websocketClient
    ) {
        $this->consumerHeartbeat = $consumerHeartbeat;
        $this->connectionChecker = $connectionChecker;
        $this->websocketClient = $websocketClient;
        parent::__construct();
    }

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
        $this->setDescription('Checks if there is alive consumers');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // do nothing if check was disabled with 0 config option value
        if ($this->getContainer()->getParameter(self::HEARTBEAT_UPDATE_PERIOD_PARAMETER_NAME) === 0) {
            return;
        }

        if (!$this->consumerHeartbeat->isAlive() && $this->connectionChecker->checkConnection()) {
            // Notify frontend that there are no alive consumers.
            $this->websocketClient->publish('oro/message_queue_heartbeat', '');
        }
    }
}
