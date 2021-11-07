<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Connector;

use Oro\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Job\Job;
use Oro\Bundle\BatchBundle\Job\JobFactory;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\BatchBundle\Step\StepFactory;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemProcessorTestHelper;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemReaderTestHelper;
use Oro\Bundle\BatchBundle\Tests\Unit\Item\ItemWriterTestHelper;

class ConnectorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $jobFactory;

    /** @var StepFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $stepFactory;

    /** @var ConnectorRegistry */
    private ConnectorRegistry $registry;

    protected function setUp(): void
    {
        $this->jobFactory = $this->createMock(JobFactory::class);
        $this->stepFactory = $this->createMock(StepFactory::class);

        $this->registry = new ConnectorRegistry($this->jobFactory, $this->stepFactory);
    }

    public function testAddStepToNotExistentJob(): void
    {
        $job = $this->createMock(Job::class);
        $step = $this->createMock(ItemStep::class);
        $reader = $this->createMock(ItemReaderTestHelper::class);
        $processor = $this->createMock(ItemProcessorTestHelper::class);
        $writer = $this->createMock(ItemWriterTestHelper::class);

        $this->jobFactory
            ->expects(self::once())
            ->method('createJob')
            ->with('Export some stuff')
            ->willReturn($job);

        $this->stepFactory
            ->expects(self::once())
            ->method('createStep')
            ->with(
                'Export',
                ItemStep::class,
                [
                    'reader' => $reader,
                    'processor' => $processor,
                    'writer' => $writer,
                ],
                []
            )
            ->willReturn($step);

        $job->expects(self::once())
            ->method('addStep')
            ->with($step);

        $this->registry->addStepToJob(
            'Akeneo',
            JobInstance::TYPE_EXPORT,
            'export_stuff',
            'Export some stuff',
            'Export',
            ItemStep::class,
            [
                'reader' => $reader,
                'processor' => $processor,
                'writer' => $writer,
            ],
            []
        );

        self::assertEquals(
            [
                'Akeneo' => [
                    'export_stuff' => $job,
                ],
            ],
            $this->registry->getJobs(JobInstance::TYPE_EXPORT)
        );
    }

    public function testAddStepToExistentJob(): void
    {
        $job = $this->createMock(Job::class);
        $step0 = $this->createMock(ItemStep::class);
        $step1 = $this->createMock(ItemStep::class);
        $reader = $this->createMock(ItemReaderTestHelper::class);
        $processor = $this->createMock(ItemProcessorTestHelper::class);
        $writer = $this->createMock(ItemWriterTestHelper::class);

        $this->jobFactory
            ->expects(self::once())
            ->method('createJob')
            ->with('Export some stuff')
            ->willReturn($job);

        $this->stepFactory
            ->expects(self::exactly(2))
            ->method('createStep')
            ->willReturnOnConsecutiveCalls($step0, $step1);

        $job->expects(self::exactly(2))
            ->method('addStep');

        $this->registry->addStepToJob(
            'Akeneo',
            JobInstance::TYPE_EXPORT,
            'export_stuff',
            'Export some stuff',
            'Export',
            ItemStep::class,
            [
                'reader' => $reader,
                'processor' => $processor,
                'writer' => $writer,
            ],
            []
        );

        $this->registry->addStepToJob(
            'Akeneo',
            JobInstance::TYPE_EXPORT,
            'export_stuff',
            'Export some stuff',
            'Export2',
            ItemStep::class,
            [
                'reader' => $reader,
                'processor' => $processor,
                'writer' => $writer,
            ],
            []
        );

        self::assertEquals(
            [
                'Akeneo' => [
                    'export_stuff' => $job,
                ],
            ],
            $this->registry->getJobs(JobInstance::TYPE_EXPORT)
        );
    }

    public function testGetUnknownJob(): void
    {
        self::assertNull($this->registry->getJob(new JobInstance('Akeneo', JobInstance::TYPE_EXPORT, 'export_stuff')));
    }
}
