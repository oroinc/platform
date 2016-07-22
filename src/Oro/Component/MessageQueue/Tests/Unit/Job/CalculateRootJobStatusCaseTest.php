<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Oro\Component\MessageQueue\Job\CalculateRootJobStatusCase;
use Oro\Component\MessageQueue\Job\Job;

class CalculateRootJobStatusCaseTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CalculateRootJobStatusCase($this->createEntityManagerMock());
    }

    public function stopStatusProvider()
    {
        return [
            [Job::STATUS_SUCCESS],
            [Job::STATUS_FAILED],
            [Job::STATUS_CANCELLED],
        ];
    }

    /**
     * @dataProvider stopStatusProvider
     */
    public function testShouldDoNothingIfRootJobHasStopState($status)
    {
        $rootJob = new Job();
        $rootJob->setStatus($status);

        $notRootJob = new Job();
        $notRootJob->setRootJob($rootJob);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->never())
            ->method('transactional')
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($notRootJob);
    }

    public function testShouldCalculateRootJobStatus()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_RUNNING);

        $rootJob->setChildJobs([$childJob]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->with(Job::class, 123, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($childJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
        $this->assertNull($rootJob->getStoppedAt());
    }

    /**
     * @dataProvider stopStatusProvider
     */
    public function testShouldCalculateRootJobStatusAndSetUniqueNameNullAndSetStoppedAtTimeIfGotStopStatus($stopStatus)
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setUniqueName('unique-name');

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus($stopStatus);

        $rootJob->setChildJobs([$childJob]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->with(Job::class, 123, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($childJob);

        $this->assertEquals($stopStatus, $rootJob->getStatus());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
        $this->assertNull($rootJob->getUniqueName());
    }

    public function testShouldSetStoppedAtOnlyIfWasNotSet()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setUniqueName('unique-name');
        $rootJob->setStoppedAt(new \DateTime('2012-12-12 12:12:12'));

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->with(Job::class, 123, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($childJob);

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $rootJob->getStoppedAt());
    }

    public function testShouldThrowIfInvalidStatus()
    {
        $rootJob = new Job();

        $childJob = new Job();
        $childJob->setId(12345);
        $childJob->setRootJob($rootJob);
        $childJob->setStatus('invalid-status');

        $rootJob->setChildJobs([$childJob]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);

        $this->setExpectedException(
            \LogicException::class,
            'Got unsupported job status: id: "12345" status: "invalid-status"'
        );

        $case->calculate($childJob);
    }

    public function testShouldSetStatusNewIfAllChildAreNew()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_NEW);

        $rootJob->setChildJobs([$childJob1, $childJob2]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_NEW, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfAnyOneIsRunning()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_RUNNING);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfThereIsNoRunningButNewAndAnyOfStopStatus()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusCancelledIfAllIsStopButOneIsCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_CANCELLED, $rootJob->getStatus());
    }

    public function testShouldSetStatusFailedIfThereIsAnyOneIsFailedButIsNotCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_FAILED, $rootJob->getStatus());
    }

    public function testShouldSetStatusSuccessIfAllAreSuccess()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($rootJob))
        ;

        $case = new CalculateRootJobStatusCase($em);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_SUCCESS, $rootJob->getStatus());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->getMock(EntityManager::class, [], [], '', false);
    }
}
