<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Extension;

use Oro\Component\MessageQueue\Job\Extension\ChainExtension;
use Oro\Component\MessageQueue\Job\Extension\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainExtensionTest extends TestCase
{
    protected ChainExtension $chainExtension;
    protected ExtensionInterface&MockObject $subExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->subExtension = $this->createMock(ExtensionInterface::class);
        $this->chainExtension = new ChainExtension([$this->subExtension]);
    }

    public function testOnPreRunUnique(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($job);

        $this->chainExtension->onPreRunUnique($job);
    }

    public function testOnPostRunUnique(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($job, true);

        $this->chainExtension->onPostRunUnique($job, true);
    }

    public function testOnPreRunDelayed(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($job);

        $this->chainExtension->onPreRunDelayed($job);
    }

    public function testOnPostRunDelayed(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($job, true);

        $this->chainExtension->onPostRunDelayed($job, true);
    }

    public function testOnPreCreateDelayed(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($job);

        $this->chainExtension->onPreCreateDelayed($job);
    }

    public function testOnPostCreateDelayed(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostCreateDelayed')
            ->with($job, true);

        $this->chainExtension->onPostCreateDelayed($job, true);
    }

    public function testOnCancel(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onCancel')
            ->with($job);

        $this->chainExtension->onCancel($job);
    }

    public function testOnError(): void
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onError')
            ->with($job);

        $this->chainExtension->onError($job);
    }
}
