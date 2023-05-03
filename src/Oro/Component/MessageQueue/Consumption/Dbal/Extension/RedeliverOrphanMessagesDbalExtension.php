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
    private int $checkInterval = 60; // 1 min

    private int $lastCheckTime = 0;

    private bool $processPidSaved;

    private DbalPidFileManager $pidFileManager;

    private DbalCliProcessManager $cliProcessManager;

    private string $consumerProcessPattern;

    private string $activeTransportName;

    public function __construct(
        DbalPidFileManager $pidFileManager,
        DbalCliProcessManager $cliProcessManager,
        string $consumerProcessPattern,
        string $activeTransportName = 'dbal',
    ) {
        $this->pidFileManager = $pidFileManager;
        $this->cliProcessManager = $cliProcessManager;
        $this->consumerProcessPattern = $consumerProcessPattern;
        $this->activeTransportName = $activeTransportName;
        $this->processPidSaved = false;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        if (!$this->isApplicable()) {
            return;
        }

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
        if (!$this->isApplicable()) {
            return;
        }

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

    private function saveProcessPid(Context $context)
    {
        /** @var DbalMessageConsumer $consumer */
        $consumer = $context->getMessageConsumer();

        $this->pidFileManager->createPidFile($consumer->getConsumerId());
    }

    private function redeliverOrphanMessages(Context $context)
    {
        $pidsFileInfo = $this->pidFileManager->getListOfPidsFileInfo();
        if (!$pidsFileInfo) {
            return;
        }

        // Finds currently running consumers.
        $runningPids = $this->cliProcessManager->getListOfProcessesPids($this->consumerProcessPattern);
        $orphanConsumerIds = [];
        foreach ($pidsFileInfo as $pidFileInfo) {
            if (!in_array($pidFileInfo['pid'], $runningPids, false)) {
                $orphanConsumerIds[] = $pidFileInfo['consumerId'];
            }
        }

        if (!$orphanConsumerIds) {
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

    private function isApplicable(): bool
    {
        return $this->activeTransportName === 'dbal';
    }
}
