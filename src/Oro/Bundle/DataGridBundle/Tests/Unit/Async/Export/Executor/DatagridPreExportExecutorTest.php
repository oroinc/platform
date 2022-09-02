<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export\Executor;

use Oro\Bundle\DataGridBundle\Async\Export\Executor\DatagridPreExportExecutor;
use Oro\Bundle\DataGridBundle\Async\Export\Executor\DatagridPreExportExecutorInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

class DatagridPreExportExecutorTest extends \PHPUnit\Framework\TestCase
{
    public function testRunWhenNoExecutors(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    'Job executor is not found for the job #%s, datagrid %s, options: %s',
                    $job->getName(),
                    $datagrid->getName(),
                    json_encode($options, JSON_THROW_ON_ERROR)
                )
            )
        );

        $executor = new DatagridPreExportExecutor([]);
        $executor->run($this->createMock(JobRunner::class), $job, $datagrid, $options);
    }

    public function testRunWhenNotSupported(): void
    {
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    'Job executor is not found for the job #%s, datagrid %s, options: %s',
                    $job->getName(),
                    $datagrid->getName(),
                    json_encode($options, JSON_THROW_ON_ERROR)
                )
            )
        );

        $sampleExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(false);

        $executor = new DatagridPreExportExecutor([$sampleExecutor]);
        $executor->run($this->createMock(JobRunner::class), $job, $datagrid, $options);
    }

    public function testRunWhenSupported(): void
    {
        $jobRunner = $this->createMock(JobRunner::class);
        $job = new Job();
        $job->setName('sample.job');
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $sampleNotSupportedExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleNotSupportedExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(false);

        $sampleNotSupportedExecutor
            ->expects(self::never())
            ->method('run');

        $sampleExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(true);

        $sampleExecutor
            ->expects(self::once())
            ->method('run')
            ->with($jobRunner, $job, $datagrid, $options)
            ->willReturn(true);

        $executor = new DatagridPreExportExecutor([$sampleNotSupportedExecutor, $sampleExecutor]);
        self::assertTrue($executor->run($jobRunner, $job, $datagrid, $options));
    }

    public function testIsSupportedWhenNoExecutors(): void
    {
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $executor = new DatagridPreExportExecutor([]);
        self::assertFalse($executor->isSupported($datagrid, $options));
    }

    public function testIsSupportedWhenNotSupported(): void
    {
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $sampleExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(false);

        $executor = new DatagridPreExportExecutor([$sampleExecutor]);
        self::assertFalse($executor->isSupported($datagrid, $options));
    }

    public function testIsSupportedWhenSupported(): void
    {
        $datagrid = new Datagrid('sample-datagrid', DatagridConfiguration::create([]), new ParameterBag());
        $options = ['sample-key' => 'sample-value'];

        $sampleNotSupportedExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleNotSupportedExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(false);

        $sampleExecutor = $this->createMock(DatagridPreExportExecutorInterface::class);
        $sampleExecutor
            ->expects(self::once())
            ->method('isSupported')
            ->with($datagrid, $options)
            ->willReturn(true);

        $executor = new DatagridPreExportExecutor([$sampleNotSupportedExecutor, $sampleExecutor]);
        self::assertTrue($executor->isSupported($datagrid, $options));
    }
}
