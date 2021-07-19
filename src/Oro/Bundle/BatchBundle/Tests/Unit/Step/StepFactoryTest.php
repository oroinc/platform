<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Doctrine\Inflector\InflectorFactory;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\BatchBundle\Step\StepFactory;
use Oro\Bundle\BatchBundle\Step\StepInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StepFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateStep(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $jobRepository = $this->createMock(JobRepositoryInterface::class);

        $stepFactory = new StepFactory($eventDispatcher, $jobRepository, InflectorFactory::create()->build());

        $reader = $this->createMock(ItemReaderInterface::class);
        $processor = $this->createMock(ItemProcessorInterface::class);
        $writer = $this->createMock(ItemWriterInterface::class);

        $services = ['reader' => $reader, 'processor' => $processor, 'writer' => $writer];
        $class = ItemStep::class;
        $step = $stepFactory->createStep('my_test_job', $class, $services, []);

        self::assertInstanceOf(StepInterface::class, $step);
        self::assertEquals($reader, $step->getReader());
        self::assertEquals($processor, $step->getProcessor());
        self::assertEquals($writer, $step->getWriter());
        self::assertEquals($jobRepository, $step->getJobRepository());
    }
}
