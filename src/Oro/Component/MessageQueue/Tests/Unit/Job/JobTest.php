<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\Job;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobTest extends TestCase
{
    public function testShouldConstructWithNullId(): void
    {
        $job = new Job();
        self::assertNull($job->getId());
    }

    public function testShouldConstructWithNullOwnerId(): void
    {
        $job = new Job();
        self::assertNull($job->getOwnerId());
    }

    public function testShouldConstructWithNullName(): void
    {
        $job = new Job();
        self::assertNull($job->getName());
    }

    public function testShouldConstructWithNullStatus(): void
    {
        $job = new Job();
        self::assertNull($job->getStatus());
    }

    public function testShouldConstructWithNullJobProgress(): void
    {
        $job = new Job();
        self::assertNull($job->getJobProgress());
    }

    public function testShouldConstructNotInterrupted(): void
    {
        $job = new Job();
        self::assertFalse($job->isInterrupted());
    }

    public function testShouldConstructNotUnique(): void
    {
        $job = new Job();
        self::assertFalse($job->isUnique());
    }

    public function testShouldConstructWithNullRootJob(): void
    {
        $job = new Job();
        self::assertNull($job->getRootJob());
        self::assertTrue($job->isRoot());
    }

    public function testShouldConstructWithNullChildJobs(): void
    {
        $job = new Job();
        self::assertEmpty($job->getChildJobs());
    }

    public function testShouldConstructWithNullCreatedAt(): void
    {
        $job = new Job();
        self::assertNull($job->getCreatedAt());
    }

    public function testShouldConstructWithNullStartedAt(): void
    {
        $job = new Job();
        self::assertNull($job->getStartedAt());
    }

    public function testShouldConstructWithNullStoppedAt(): void
    {
        $job = new Job();
        self::assertNull($job->getStoppedAt());
    }

    public function testShouldConstructWithEmptyData(): void
    {
        $job = new Job();
        self::assertSame([], $job->getData());
    }

    public function testShouldConstructWithEmptyProperties(): void
    {
        $job = new Job();
        self::assertSame([], $job->getProperties());
    }

    public function testShouldBePossibleToSetId(): void
    {
        $job = new Job();

        $job->setId(123);
        self::assertSame(123, $job->getId());
    }

    public function testShouldBePossibleToSetOwnerId(): void
    {
        $job = new Job();

        $job->setOwnerId('123');
        self::assertSame('123', $job->getOwnerId());
    }

    public function testShouldBePossibleToSetName(): void
    {
        $job = new Job();

        $job->setName('test');
        self::assertEquals('test', $job->getName());
    }

    public function testShouldBePossibleToSetStatus(): void
    {
        $job = new Job();

        $job->setStatus(Job::STATUS_RUNNING);
        self::assertEquals(Job::STATUS_RUNNING, $job->getStatus());
    }

    public function testShouldBePossibleToSetJobProgress(): void
    {
        $job = new Job();

        $job->setJobProgress(1.23);
        self::assertSame(1.23, $job->getJobProgress());
    }

    public function testShouldBePossibleToSetInterrupted(): void
    {
        $job = new Job();

        $job->setInterrupted(true);
        self::assertTrue($job->isInterrupted());
    }

    public function testShouldBePossibleToSetUnique(): void
    {
        $job = new Job();

        $job->setUnique(true);
        self::assertTrue($job->isUnique());
    }

    public function testShouldBePossibleToSetRootJob(): void
    {
        $job = new Job();

        $rootJob = new Job();
        $job->setRootJob($rootJob);
        self::assertSame($rootJob, $job->getRootJob());
        self::assertFalse($job->isRoot());
    }

    public function testShouldBePossibleToSetCreatedAt(): void
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setCreatedAt($date);
        self::assertSame($date, $job->getCreatedAt());
    }

    public function testShouldBePossibleToSetStartedAt(): void
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setStartedAt($date);
        self::assertSame($date, $job->getStartedAt());
    }

    public function testShouldBePossibleToSetStoppedAt(): void
    {
        $job = new Job();

        $date = new \DateTime();
        $job->setStoppedAt($date);
        self::assertSame($date, $job->getStoppedAt());
    }

    public function testShouldBePossibleToAddChildJob(): void
    {
        $job = new Job();

        $childJob = new Job();
        $job->addChildJob($childJob);
        $childJobs = $job->getChildJobs();
        self::assertCount(1, $childJobs);
        self::assertSame($childJob, $childJobs[0]);
    }

    public function testShouldBePossibleToSetChildJobs(): void
    {
        $job = new Job();

        $childJob = new Job();
        $job->setChildJobs([$childJob]);
        $childJobs = $job->getChildJobs();
        self::assertCount(1, $childJobs);
        self::assertSame($childJob, $childJobs[0]);
    }

    public function testShouldBePossibleToSetData(): void
    {
        $job = new Job();

        $data = ['key' => 'value'];
        $job->setData($data);
        self::assertSame($data, $job->getData());
    }

    public function testShouldBePossibleToSetProperties(): void
    {
        $job = new Job();

        $properties = ['key' => 'value'];
        $job->setProperties($properties);
        self::assertSame($properties, $job->getProperties());
    }

    public function testShouldBePossibleToKeepPropertiesAsIsWhenDataIsChanged(): void
    {
        $job = new Job();

        $properties = ['property' => 'property_value'];
        $job->setProperties($properties);

        $data = ['key' => 'value'];
        $job->setData($data);

        self::assertSame($properties, $job->getProperties());
        self::assertSame(array_merge($data, ['_properties' => $properties]), $job->getData());
    }
}
