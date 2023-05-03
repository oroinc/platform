<?php

namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\Executor\DatagridPreExportExecutorInterface;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Prepares datagrid data export.
 */
class DatagridPreExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    private DatagridPreExportExecutorInterface $datagridPreExportExecutor;

    private DatagridManager $datagridManager;

    public function __construct(
        JobRunner $jobRunner,
        DatagridPreExportExecutorInterface $datagridPreExportExecutor,
        DatagridManager $datagridManager
    ) {
        $this->jobRunner = $jobRunner;
        $this->datagridPreExportExecutor = $datagridPreExportExecutor;
        $this->datagridManager = $datagridManager;
    }

    public static function getSubscribedTopics(): array
    {
        return [DatagridPreExportTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($messageBody) {
                $datagrid = $this->datagridManager->getDatagrid(
                    $messageBody['contextParameters']['gridName'],
                    $messageBody['contextParameters']['gridParameters']
                );

                return $this->datagridPreExportExecutor->run($jobRunner, $job, $datagrid, $messageBody);
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
