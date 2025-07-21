<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\MessageQueueBundle\Datagrid\RootJobActionConfiguration;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use PHPUnit\Framework\TestCase;

class RootJobActionConfigurationTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new RootJobActionConfiguration();
    }

    public function testShouldReturnViewActionTrueAllTheTime(): void
    {
        $job = new Job();
        $resultRecord = new ResultRecord($job);

        $configuration = new RootJobActionConfiguration();
        $result = $configuration->getConfiguration($resultRecord);

        $this->assertArrayHasKey('view', $result);
        $this->assertTrue($result['view']);
    }

    public function testShouldReturnInterruptRootJobActionTrueIfJobIsNotInterrupted(): void
    {
        $job = new Job();
        $resultRecord = new ResultRecord($job);

        $configuration = new RootJobActionConfiguration();
        $result = $configuration->getConfiguration($resultRecord);

        $this->assertArrayHasKey('interrupt_root_job', $result);
        $this->assertTrue($result['interrupt_root_job']);
    }

    public function testShouldReturnInterruptRootJobActionFalseIfJobIsInterrupted(): void
    {
        $job = new Job();
        $job->setInterrupted(true);
        $resultRecord = new ResultRecord($job);

        $configuration = new RootJobActionConfiguration();
        $result = $configuration->getConfiguration($resultRecord);

        $this->assertArrayHasKey('interrupt_root_job', $result);
        $this->assertFalse($result['interrupt_root_job']);
    }
}
