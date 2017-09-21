<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldConstructWithNullId()
    {
        $job = new Job();
        self::assertNull($job->getId());
    }

    public function testShouldConstructWithNullOwnerId()
    {
        $job = new Job();
        self::assertNull($job->getOwnerId());
    }

    public function testShouldConstructWithNullName()
    {
        $job = new Job();
        self::assertNull($job->getName());
    }

    public function testShouldConstructWithNullStatus()
    {
        $job = new Job();
        self::assertNull($job->getStatus());
    }

    public function testShouldConstructWithNullJobProgress()
    {
        $job = new Job();
        self::assertNull($job->getJobProgress());
    }

    public function testShouldConstructNotInterrupted()
    {
        $job = new Job();
        self::assertFalse($job->isInterrupted());
    }

    public function testShouldConstructNotUnique()
    {
        $job = new Job();
        self::assertFalse($job->isUnique());
    }

    public function testShouldConstructWithNullRootJob()
    {
        $job = new Job();
        self::assertNull($job->getRootJob());
        self::assertTrue($job->isRoot());
    }

    public function testShouldConstructWithNullChildJobs()
    {
        $job = new Job();
        self::assertNull($job->getChildJobs());
    }

    public function testShouldConstructWithNullCreatedAt()
    {
        $job = new Job();
        self::assertNull($job->getCreatedAt());
    }

    public function testShouldConstructWithNullStartedAt()
    {
        $job = new Job();
        self::assertNull($job->getStartedAt());
    }

    public function testShouldConstructWithNullStoppedAt()
    {
        $job = new Job();
        self::assertNull($job->getStoppedAt());
    }

    public function testShouldConstructWithEmptyData()
    {
        $job = new Job();
        self::assertSame([], $job->getData());
    }

    public function testShouldConstructWithEmptyProperties()
    {
        $job = new Job();
        self::assertSame([], $job->getProperties());
    }

    public function testShouldBePossibleToSetId()
    {
        $job = new Job();

        $job->setId(123);
        self::assertSame(123, $job->getId());
    }

    public function testShouldBePossibleToSetOwnerId()
    {
        $job = new Job();

        $job->setOwnerId(123);
        self::assertSame(123, $job->getOwnerId());
    }

    public function testShouldBePossibleToSetName()
    {
        $job = new Job();

        $job->setName('test');
        self::assertEquals('test', $job->getName());
    }

    public function testShouldBePossibleToSetStatus()
    {
        $job = new Job();

        $job->setStatus(Job::STATUS_RUNNING);
        self::assertEquals(Job::STATUS_RUNNING, $job->getStatus());
    }

    public function testShouldBePossibleToSetJobProgress()
    {
        $job = new Job();

        $job->setJobProgress(1.23);
        self::assertSame(1.23, $job->getJobProgress());
    }

    public function testShouldBePossibleToSetInterrupted()
    {
        $job = new Job();

        $job->setInterrupted(true);
        self::assertTrue($job->isInterrupted());
    }

    public function testShouldBePossibleToSetUnique()
    {
        $job = new Job();

        $job->setUnique(true);
        self::assertTrue($job->isUnique());
    }

    public function testShouldBePossibleToSetRootJob()
    {
        $job = new Job();

        $rootJob = new Job();
        $job->setRootJob($rootJob);
        self::assertSame($rootJob, $job->getRootJob());
        self::assertFalse($job->isRoot());
    }

    public function testShouldBePossibleToSetCreatedAt()
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setCreatedAt($date);
        self::assertSame($date, $job->getCreatedAt());
    }

    public function testShouldBePossibleToSetStartedAt()
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setStartedAt($date);
        self::assertSame($date, $job->getStartedAt());
    }

    public function testShouldBePossibleToSetStoppedAt()
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setStoppedAt($date);
        self::assertSame($date, $job->getStoppedAt());
    }

    public function testShouldBePossibleToAddChildJob()
    {
        $job = new Job();

        $childJob = new Job();
        $job->addChildJob($childJob);
        $childJobs = $job->getChildJobs();
        self::assertCount(1, $childJobs);
        self::assertSame($childJob, $childJobs[0]);
    }

    public function testShouldBePossibleToSetChildJobs()
    {
        $job = new Job();

        $childJob = new Job();
        $job->setChildJobs([$childJob]);
        $childJobs = $job->getChildJobs();
        self::assertCount(1, $childJobs);
        self::assertSame($childJob, $childJobs[0]);
    }

    public function testShouldBePossibleToSetData()
    {
        $job = new Job();

        $data = ['key' => 'value'];
        $job->setData($data);
        self::assertSame($data, $job->getData());
    }

    public function testShouldBePossibleToSetProperties()
    {
        $job = new Job();

        $properties = ['key' => 'value'];
        $job->setProperties($properties);
        self::assertSame($properties, $job->getProperties());
    }

    public function testShouldBePossibleToKeepPropertiesAsIsWhenDataIsChanged()
    {
        $job = new Job();

        $properties = ['property' => 'property_value'];
        $job->setProperties($properties);

        $data = ['key' => 'value'];
        $job->setData($data);

        self::assertSame($properties, $job->getProperties());
        self::assertSame($data, $job->getData());
    }
}
