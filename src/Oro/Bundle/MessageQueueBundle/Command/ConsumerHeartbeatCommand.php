<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Command\SynchronousCommandInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron command that checks that consumers are alive and pushes the message if there are no available consumers.
 */
class ConsumerHeartbeatCommand extends Command implements
    CronCommandInterface,
    SynchronousCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:message-queue:consumer_heartbeat_check';

    /** @var ConsumerHeartbeat */
    private $consumerHeartbeat;

    /** var ConnectionChecker **/
    private $connectionChecker;

    /** var WebsocketClientInterface **/
    private $websocketClient;

    /** @var int */
    private $heartBeatUpdatePeriod;

    /**
     * @param ConsumerHeartbeat $consumerHeartbeat
     * @param ConnectionChecker $connectionChecker
     * @param WebsocketClientInterface $websocketClient
     * @param int $heartBeatUpdatePeriod
     */
    public function __construct(
        ConsumerHeartbeat $consumerHeartbeat,
        ConnectionChecker $connectionChecker,
        WebsocketClientInterface $websocketClient,
        int $heartBeatUpdatePeriod
    ) {
        $this->consumerHeartbeat = $consumerHeartbeat;
        $this->connectionChecker = $connectionChecker;
        $this->websocketClient = $websocketClient;
        $this->heartBeatUpdatePeriod = $heartBeatUpdatePeriod;
        parent::__construct();
    }

    /** {@inheritdoc} */
    public function getDefaultDefinition()
    {
        return sprintf(
            '*/%s * * * *',
            $this->heartBeatUpdatePeriod
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
        if ($this->heartBeatUpdatePeriod === 0) {
            return;
        }

        if (!$this->consumerHeartbeat->isAlive() && $this->connectionChecker->checkConnection()) {
            // Notify frontend that there are no alive consumers.
            $this->websocketClient->publish('oro/message_queue_heartbeat', '');
        }
    }
}
