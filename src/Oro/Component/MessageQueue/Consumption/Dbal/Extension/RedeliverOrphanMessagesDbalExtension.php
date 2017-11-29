<?php

namespace Oro\Component\MessageQueue\Consumption\Dbal\Extension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class RedeliverOrphanMessagesDbalExtension extends AbstractExtension
{
    /**
     * @var int
     */
    private $checkInterval = 60; // 1 min

    /**
     * Static for fix losing value during reset of container
     * @var int
     */
    private static $lastCheckTime = 0;

    /**
     * @var bool
     */
    private $processPidSaved;

    /**
     * @var DbalPidFileManager
     */
    private $pidFileManager;

    /**
     * @var DbalCliProcessManager
     */
    private $cliProcessManager;

    /**
     * @var string
     */
    private $consumerProcessPattern;

    /**
     * @param DbalPidFileManager $pidFileManager
     * @param DbalCliProcessManager $cliProcessManager
     * @param string $consumerProcessPattern
     */
    public function __construct(
        DbalPidFileManager $pidFileManager,
        DbalCliProcessManager $cliProcessManager,
        $consumerProcessPattern
    ) {
        $this->pidFileManager = $pidFileManager;
        $this->cliProcessManager = $cliProcessManager;
        $this->consumerProcessPattern = $consumerProcessPattern;
        $this->processPidSaved = false;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        /** @var DbalSession $session */
        $session = $context->getSession();
        if (false == $session instanceof DbalSession) {
            throw new \LogicException(sprintf(
                'Unexpected instance of session. expected:"%s", actual:"%s"',
                DbalSession::class,
                is_object($session) ? get_class($session) : gettype($session)
            ));
        }

        if (! $this->processPidSaved) {
            $this->processPidSaved = true;
            $this->saveProcessPid($context);
        }

        if ($this->shouldCheck()) {
            $this->redeliverOrphanMessages($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        /** @var DbalSession $session */
        $session = $context->getSession();
        if (false == $session instanceof DbalSession) {
            throw new \LogicException(sprintf(
                'Unexpected instance of session. expected:"%s", actual:"%s"',
                DbalSession::class,
                is_object($session) ? get_class($session) : gettype($session)
            ));
        }

        $this->pidFileManager->removePidFile($context->getMessageConsumer()->getConsumerId());
    }

    /**
     * @param Context $context
     */
    private function saveProcessPid(Context $context)
    {
        /** @var DbalMessageConsumer $consumer */
        $consumer = $context->getMessageConsumer();

        $this->pidFileManager->createPidFile($consumer->getConsumerId());
    }

    /**
     * @param Context $context
     */
    private function redeliverOrphanMessages(Context $context)
    {
        // find orphan consumerIds
        $runningPids = $this->cliProcessManager->getListOfProcessesPids($this->consumerProcessPattern);
        $runningConsumerIds = $orphanConsumerIds = [];
        foreach ($this->pidFileManager->getListOfPidsFileInfo() as $pidFileInfo) {
            if (in_array($pidFileInfo['pid'], $runningPids, true)) {
                $runningConsumerIds[] = $pidFileInfo['consumerId'];
            } else {
                $orphanConsumerIds[] = $pidFileInfo['consumerId'];
            }
        }

        // redeliver orphan messages
        /** @var DbalSession $session */
        $session = $context->getSession();
        $connection = $session->getConnection();
        $dbal = $connection->getDBALConnection();

        // After server reboot tmp files was lost, so here we redeliver all messages without active consumer
        $sql = sprintf(
            'UPDATE %s SET consumer_id=NULL, redelivered=:isRedelivered '.
            'WHERE consumer_id NOT IN (:consumerIds) AND consumer_id IS NOT NULL',
            $connection->getTableName()
        );

        $affectedRows = $dbal->executeUpdate(
            $sql,
            [
                'isRedelivered' => true,
                'consumerIds' => $runningConsumerIds,
            ],
            [
                'isRedelivered' => Type::BOOLEAN,
                'consumerIds' => Connection::PARAM_STR_ARRAY,
            ]
        );

        // remove pid files
        foreach ($orphanConsumerIds as $consumerId) {
            $this->pidFileManager->removePidFile($consumerId);
        }

        if ($affectedRows) {
            $context->getLogger()->warning(sprintf(
                'Orphans were found. Count of redelivered messages: %s. Running consumers: "%s"',
                $affectedRows,
                implode(', ', $runningConsumerIds)
            ));
        }
    }

    /**
     * @return bool
     */
    private function shouldCheck()
    {
        $time = time();

        if (($time - self::$lastCheckTime) < $this->checkInterval) {
            return false;
        }

        self::$lastCheckTime = $time;

        return true;
    }
}
