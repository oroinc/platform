<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export\Executor;

use Oro\Bundle\DataGridBundle\Async\Export\Executor\DatagridPreExportOrmExecutor;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\MaterializedView\MaterializedViewByDatagridFactory;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteMaterializedViewTopic;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

class DatagridPreExportOrmExecutorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private MaterializedViewManager|\PHPUnit\Framework\MockObject\MockObject $materializedViewManager;

    private MaterializedViewByDatagridFactory|\PHPUnit\Framework\MockObject\MockObject
        $materializedViewByDatagridFactory;

    private DependentJobService|\PHPUnit\Framework\MockObject\MockObject $dependentJobService;

    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    private DatagridPreExportOrmExecutor $executor;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->materializedViewManager = $this->createMock(MaterializedViewManager::class);
        $this->materializedViewByDatagridFactory = $this->createMock(MaterializedViewByDatagridFactory::class);
        $this->dependentJobService = $this->createMock(DependentJobService::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->executor = new DatagridPreExportOrmExecutor(
            $this->messageProducer,
            $this->materializedViewManager,
            $this->materializedViewByDatagridFactory,
            $this->dependentJobService,
            $this->tokenAccessor
        );

        $this->setUpLoggerMock($this->executor);
    }

    public function testIsSupportedWhenNotOrm(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $datagrid->setDatasource(new ArrayDatasource());
        $options = ['sample-key' => 'sample-value'];

        $this->tokenAccessor
            ->expects(self::any())
            ->method('getUserId')
            ->willReturn(42);

        self::assertFalse($this->executor->isSupported($datagrid, $options));
    }

    public function testIsSupportedWhenNoUser(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $datagrid->setDatasource($this->createMock(OrmDatasource::class));
        $options = ['sample-key' => 'sample-value'];

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn(null);

        self::assertFalse($this->executor->isSupported($datagrid, $options));
    }

    public function testIsSupported(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $datagrid->setDatasource($this->createMock(OrmDatasource::class));
        $options = ['sample-key' => 'sample-value'];

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn(42);

        self::assertTrue($this->executor->isSupported($datagrid, $options));
    }

    public function runWhenNoUser(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Cannot execute job {jobName}: no authenticated user is found',
                ['jobName' => $job->getName(), 'datagrid' => $datagrid, 'options' => $options]
            );

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn(null);

        $this->executor->run($this->createMock(JobRunner::class), $job, $datagrid, $options);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRunWhenNoRowsCount(): void
    {
        $jobRunner = $this->createMock(JobRunner::class);
        $rootJob = new Job();
        $rootJob->setName('sample.job.root');
        $job = new Job();
        $job->setName('sample.job');
        $job->setRootJob($rootJob);
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = [
            'outputFormat' => 'csv',
            'notificationTemplate' => 'sample-template',
            'contextParameters' => ['gridName' => $datagrid->getName()],
            'batchSize' => 4242,
        ];

        $ormDatasource = $this->createMock(OrmDatasource::class);
        $ormDatasource
            ->expects(self::once())
            ->method('getRootEntityName')
            ->willReturn(\stdClass::class);
        $datagrid->setDatasource($ormDatasource);

        $userId = 42;
        $this->tokenAccessor
            ->expects(self::any())
            ->method('getUserId')
            ->willReturn($userId);

        $materializedView = (new MaterializedView())
            ->setName('sample_materialized_view');
        $this->materializedViewByDatagridFactory
            ->expects(self::once())
            ->method('createByDatagrid')
            ->with($datagrid)
            ->willReturn($materializedView);

        $this->dependentJobService
            ->expects(self::once())
            ->method('addDependentMessages')
            ->with($rootJob, [
                DeleteMaterializedViewTopic::getName() => ['materializedViewName' => $materializedView->getName()],
                PostExportTopic::getName() => [
                    'jobId' => $rootJob->getId(),
                    'jobName' => $rootJob->getName(),
                    'recipientUserId' => $this->tokenAccessor->getUserId(),
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'outputFormat' => $options['outputFormat'],
                    'entity' => \stdClass::class,
                    'notificationTemplate' => $options['notificationTemplate'],
                ],
            ]);

        $materializedViewRepo = $this->createMock(MaterializedViewRepository::class);
        $this->materializedViewManager
            ->expects(self::once())
            ->method('getRepository')
            ->with($materializedView->getName())
            ->willReturn($materializedViewRepo);

        $materializedViewRepo
            ->expects(self::once())
            ->method('getRowsCount')
            ->willReturn(0);

        $childJob = new Job();
        $childJob->setId(142);

        $this->messageProducer
            ->expects(self::once())
            ->method('send')
            ->with(
                DatagridExportTopic::getName(),
                new Message([
                    'jobId' => $childJob->getId(),
                    'contextParameters' => [
                        'gridName' => $datagrid->getName(),
                        'materializedViewName' => $materializedView->getName(),
                        'rowsOffset' => 0,
                        'rowsLimit' => $options['batchSize'],
                    ],
                    'outputFormat' => $options['outputFormat'],
                    'writerBatchSize' => $options['batchSize'],
                ], MessagePriority::LOW)
            );

        $jobName = sprintf(
            '%s.%s.user_%s.%s.chunk.1',
            DatagridExportTopic::getName(),
            $datagrid->getName(),
            $userId,
            $options['outputFormat']
        );
        $jobRunner
            ->expects(self::once())
            ->method('createDelayed')
            ->with($jobName, self::isType('callable'))
            ->willReturnCallback(function (string $jobName, callable $callback) use ($childJob) {
                return $callback($this->createMock(JobRunner::class), $childJob);
            });

        self::assertTrue($this->executor->run($jobRunner, $job, $datagrid, $options));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRun(): void
    {
        $jobRunner = $this->createMock(JobRunner::class);
        $rootJob = new Job();
        $rootJob->setName('sample.job.root');
        $job = new Job();
        $job->setName('sample.job');
        $job->setRootJob($rootJob);
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = [
            'outputFormat' => 'csv',
            'notificationTemplate' => 'sample-template',
            'contextParameters' => ['gridName' => $datagrid->getName()],
            'batchSize' => 2,
        ];

        $ormDatasource = $this->createMock(OrmDatasource::class);
        $ormDatasource
            ->expects(self::once())
            ->method('getRootEntityName')
            ->willReturn(\stdClass::class);
        $datagrid->setDatasource($ormDatasource);

        $userId = 42;
        $this->tokenAccessor
            ->expects(self::any())
            ->method('getUserId')
            ->willReturn($userId);

        $materializedView = (new MaterializedView())
            ->setName('sample_materialized_view');
        $this->materializedViewByDatagridFactory
            ->expects(self::once())
            ->method('createByDatagrid')
            ->with($datagrid)
            ->willReturn($materializedView);

        $this->dependentJobService
            ->expects(self::once())
            ->method('addDependentMessages')
            ->with($rootJob, [
                DeleteMaterializedViewTopic::getName() => ['materializedViewName' => $materializedView->getName()],
                PostExportTopic::getName() => [
                    'jobId' => $rootJob->getId(),
                    'jobName' => $rootJob->getName(),
                    'recipientUserId' => $this->tokenAccessor->getUserId(),
                    'exportType' => ProcessorRegistry::TYPE_EXPORT,
                    'outputFormat' => $options['outputFormat'],
                    'entity' => \stdClass::class,
                    'notificationTemplate' => $options['notificationTemplate'],
                ],
            ]);

        $materializedViewRepo = $this->createMock(MaterializedViewRepository::class);
        $this->materializedViewManager
            ->expects(self::once())
            ->method('getRepository')
            ->with($materializedView->getName())
            ->willReturn($materializedViewRepo);

        $rowsCount = 3;
        $materializedViewRepo
            ->expects(self::once())
            ->method('getRowsCount')
            ->willReturn($rowsCount);

        $this->loggerMock
            ->expects(self::exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'Creating batches from {rowsCount} rows of the materialized view {materializedViewName}',
                    [
                        'rowsCount' => $rowsCount,
                        'rowsLimit' => $options['batchSize'],
                        'materializedViewName' => $materializedView->getName(),
                    ],
                ],
                [
                    'Created {count} batches from the materialized view {materializedViewName}',
                    [
                        'count' => 2,
                        'materializedViewName' => $materializedView->getName(),
                        'jobName' => $job->getName(),
                    ],
                ]
            );

        $childJob1 = new Job();
        $childJob1->setId(142);

        $childJob2 = new Job();
        $childJob2->setId(242);

        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    DatagridExportTopic::getName(),
                    new Message([
                        'jobId' => $childJob1->getId(),
                        'contextParameters' => [
                            'gridName' => $datagrid->getName(),
                            'materializedViewName' => $materializedView->getName(),
                            'rowsOffset' => 0,
                            'rowsLimit' => $options['batchSize'],
                        ],
                        'outputFormat' => $options['outputFormat'],
                        'writerBatchSize' => $options['batchSize'],
                    ], MessagePriority::LOW),
                ],
                [
                    DatagridExportTopic::getName(),
                    new Message([
                        'jobId' => $childJob2->getId(),
                        'contextParameters' => [
                            'gridName' => $datagrid->getName(),
                            'materializedViewName' => $materializedView->getName(),
                            'rowsOffset' => 2,
                            'rowsLimit' => $options['batchSize'],
                        ],
                        'outputFormat' => $options['outputFormat'],
                        'writerBatchSize' => $options['batchSize'],
                    ], MessagePriority::LOW),
                ]
            );

        $jobName = sprintf(
            '%s.%s.user_%s.%s.chunk.',
            DatagridExportTopic::getName(),
            $datagrid->getName(),
            $userId,
            $options['outputFormat']
        );
        $childJobs = [$childJob1, $childJob2];
        $jobRunner
            ->expects(self::exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [$jobName . '1', self::isType('callable')],
                [$jobName . '2', self::isType('callable')],
            )
            ->willReturnCallback(function (string $jobName, callable $callback) use (&$childJobs) {
                return $callback($this->createMock(JobRunner::class), array_shift($childJobs));
            });

        self::assertTrue($this->executor->run($jobRunner, $job, $datagrid, $options));
    }
}
