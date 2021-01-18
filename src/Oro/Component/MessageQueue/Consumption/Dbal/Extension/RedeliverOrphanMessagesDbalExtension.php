<?php

namespace Oro\Component\MessageQueue\Consumption\Dbal\Extension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSessionInterface;

/**
 * Redeliver orphan messages.
 */
class RedeliverOrphanMessagesDbalExtension extends AbstractExtension
{
    /**
     * @var int
     */
    private $checkInterval = 60; // 1 min

    /**
     * @var int
     */
    private $lastCheckTime = 0;

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
        /** @var DbalSessionInterface $session */
        $session = $context->getSession();
        if (false == $session instanceof DbalSessionInterface) {
            throw new \LogicException(sprintf(
                'Unexpected instance of session. expected:"%s", actual:"%s"',
                DbalSessionInterface::class,
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
        /** @var DbalSessionInterface $session */
        $session = $context->getSession();
        if (false == $session instanceof DbalSessionInterface) {
            throw new \LogicException(sprintf(
                'Unexpected instance of session. expected:"%s", actual:"%s"',
                DbalSessionInterface::class,
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
        $orphanConsumerIds = [];
        foreach ($this->pidFileManager->getListOfPidsFileInfo() as $pidFileInfo) {
            if (! in_array($pidFileInfo['pid'], $runningPids)) {
                $orphanConsumerIds[] = $pidFileInfo['consumerId'];
            }
        }

        if (! $orphanConsumerIds) {
            return;
        }

        // redeliver orphan messages
        /** @var DbalSessionInterface $session */
        $session = $context->getSession();
        $connection = $session->getConnection();

        $sql = sprintf(
            'UPDATE %s SET consumer_id=NULL, redelivered=:isRedelivered '.
            'WHERE consumer_id IN (:consumerIds)',
            $connection->getTableName()
        );

        /** @var Connection $dbal */
        $dbal = $connection->getDBALConnection();
        $dbal->executeStatement(
            $sql,
            [
                'isRedelivered' => true,
                'consumerIds' => $orphanConsumerIds,
            ],
            [
                'isRedelivered' => Types::BOOLEAN,
                'consumerIds' => Connection::PARAM_STR_ARRAY,
            ]
        );

        // remove pid files
        foreach ($orphanConsumerIds as $consumerId) {
            $this->pidFileManager->removePidFile($consumerId);
        }

        $context->getLogger()->critical(sprintf(
            'Orphans were found and redelivered. consumerIds: "%s"',
            implode(', ', $orphanConsumerIds)
        ));
    }

    /**
     * @return bool
     */
    private function shouldCheck()
    {
        $time = time();

        if (($time - $this->lastCheckTime) < $this->checkInterval) {
            return false;
        }

        $this->lastCheckTime = $time;

        return true;
    }
}
