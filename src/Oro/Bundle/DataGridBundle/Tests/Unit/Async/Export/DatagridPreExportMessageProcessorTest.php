<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\DatagridPreExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Export\Executor\DatagridPreExportExecutorInterface;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DatagridPreExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private DatagridPreExportExecutorInterface|\PHPUnit\Framework\MockObject\MockObject $datagridPreExportExecutor;

    private DatagridManager|\PHPUnit\Framework\MockObject\MockObject $datagridManager;

    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    private DatagridPreExportMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->datagridPreExportExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $this->datagridManager = $this->createMock(DatagridManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->processor = new DatagridPreExportMessageProcessor(
            $this->jobRunner,
            $this->datagridPreExportExecutor,
            $this->datagridManager,
            $this->tokenAccessor
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [DatagridPreExportTopic::getName()],
            DatagridPreExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $gridParameters = ['sample-key' => 'sample-value'];
        $datagrid = new Datagrid(
            'sample-datagrid',
            DatagridConfiguration::create([]),
            new ParameterBag($gridParameters)
        );
        $message = new Message();
        $message->setMessageId('sample-message-id');
        $messageBody = [
            'contextParameters' => ['gridName' => $datagrid->getName(), 'gridParameters' => $gridParameters],
            'outputFormat' => 'csv',
        ];
        $message->setBody($messageBody);

        $this->datagridManager
            ->expects(self::once())
            ->method('getDatagrid')
            ->with($datagrid->getName(), $gridParameters)
            ->willReturn($datagrid);

        $rootJobRunner = $this->createMock(JobRunner::class);
        $job = new Job();

        $this->datagridPreExportExecutor
            ->expects(self::once())
            ->method('run')
            ->with($rootJobRunner, $job, $datagrid, $message->getBody())
            ->willReturn(true);

        $userId = 42;
        $this->tokenAccessor
            ->expects(self::any())
            ->method('getUserId')
            ->willReturn($userId);

        $jobName = sprintf(
            '%s.%s.user_%s.%s',
            DatagridExportTopic::getName(),
            $datagrid->getName(),
            $userId,
            $messageBody['outputFormat']
        );

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->with($message->getMessageId(), $jobName, self::isType('callable'))
            ->willReturnCallback(
                function (string $ownerId, string $jobName, callable $callback) use ($rootJobRunner, $job) {
                    return $callback($rootJobRunner, $job);
                }
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
