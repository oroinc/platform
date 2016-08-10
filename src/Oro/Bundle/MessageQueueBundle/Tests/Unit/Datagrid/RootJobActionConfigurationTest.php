<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\MessageQueueBundle\Datagrid\RootJobActionConfiguration;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

class RootJobActionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RootJobActionConfiguration();
    }

    public function testShouldReturnViewActionTrueAllTheTime()
    {
        $job = new Job();
        $resultRecord = new ResultRecord($job);

        $configuration = new RootJobActionConfiguration();
        $result = $configuration->getConfiguration($resultRecord);

        $this->assertArrayHasKey('view', $result);
        $this->assertTrue($result['view']);
    }

    public function testShouldReturnInterruptRootJobActionTrueIfJobIsNotInterrupted()
    {
        $job = new Job();
        $resultRecord = new ResultRecord($job);

        $configuration = new RootJobActionConfiguration();
        $result = $configuration->getConfiguration($resultRecord);

        $this->assertArrayHasKey('interrupt_root_job', $result);
        $this->assertTrue($result['interrupt_root_job']);
    }

    public function testShouldReturnInterruptRootJobActionFalseIfJobIsInterrupted()
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
