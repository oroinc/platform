<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\ChainExtension;
use Oro\Component\MessageQueue\Job\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;

class ChainExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainExtension */
    protected $chainExtension;

    /** @var ExtensionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $subExtension;

    protected function setUp()
    {
        $this->subExtension = $this->createMock(ExtensionInterface::class);
        $this->chainExtension = new ChainExtension([$this->subExtension]);
    }

    public function testOnPreRunUnique()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($job);

        $this->chainExtension->onPreRunUnique($job);
    }

    public function testOnPostRunUnique()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($job, true);

        $this->chainExtension->onPostRunUnique($job, true);
    }

    public function testOnPreRunDelayed()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($job);

        $this->chainExtension->onPreRunDelayed($job);
    }

    public function testOnPostRunDelayed()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($job, true);

        $this->chainExtension->onPostRunDelayed($job, true);
    }

    public function testOnPreCreateDelayed()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($job);

        $this->chainExtension->onPreCreateDelayed($job);
    }

    public function testOnPostCreateDelayed()
    {
        $job = new Job();

        $this->subExtension->expects($this->once())
            ->method('onPostCreateDelayed')
            ->with($job, true);

        $this->chainExtension->onPostCreateDelayed($job, true);
    }
}
