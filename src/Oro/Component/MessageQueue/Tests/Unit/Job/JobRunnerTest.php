<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Exception\StaleJobRuntimeException;
use Oro\Component\MessageQueue\Job\ExtensionInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;

class JobRunnerTest extends \PHPUnit_Framework_TestCase
{
    public function testRunUniqueShouldCreateRootAndChildJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with('owner-id', 'job-name', true)
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with('job-name')
            ->will($this->returnValue($child));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, 'return-value');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $expChild = null;
        $expRunner = null;
        $result = $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function (JobRunner $runner, Job $child) use (&$expRunner, &$expChild) {
                $expRunner = $runner;
                $expChild = $child;

                return 'return-value';
            }
        );

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expChild, $child);
        $this->assertEquals('return-value', $result);
    }

    public function testRunUniqueShouldStartChildJobIfNotStarted()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->once())
            ->method('startChildJob')
            ->with($child);

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, null);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);
        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
            }
        );
    }

    public function testRunUniqueShouldReturnVoidIfRootJobIsInterrupted()
    {
        $root = new Job();
        $root->setInterrupted(true);
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->never())
            ->method('onPreRunUnique');
        $jobExtension->expects($this->never())
            ->method('onPostRunUnique');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $result = $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
            }
        );

        $this->assertNull($result);
    }

    public function testRunUniqueShouldNotStartChildJobIfAlreadyStarted()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);
        $child->setStartedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('startChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, null);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
            }
        );
    }

    public function testRunUniqueShouldStartChildJobIfAlreadyStartedButStatusFailedRedelivered()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);
        $child->setStartedAt(new \DateTime());
        $child->setStatus(Job::STATUS_FAILED_REDELIVERED);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->once())
            ->method('startChildJob')
            ->will($this->returnValue($child));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, null);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
            }
        );
    }

    public function testRunUniqueShouldSuccessJobIfCallbackReturnValueIsTrue()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->once())
            ->method('successChildJob');
        $jobProcessor->expects($this->never())
            ->method('failChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, true);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
                return true;
            }
        );
    }

    public function testRunUniqueShouldFailJobIfCallbackReturnValueIsFalse()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob');
        $jobProcessor->expects($this->once())
            ->method('failChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, false);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
                return false;
            }
        );
    }

    public function testRunUniqueShouldFailAndRedeliveryChildJobJobIfCallbackThrowException()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob');
        $jobProcessor->expects($this->once())
            ->method('failAndRedeliveryChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->never())
            ->method('onPostRunUnique');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
                throw new \Exception('test');
            }
        );
    }

    public function testRunUniqueShouldNotSuccessJobIfJobIsAlreadyStopped()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);
        $child->setStoppedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob');
        $jobProcessor->expects($this->never())
            ->method('failChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunUnique')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunUnique')
            ->with($child, true);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
                return true;
            }
        );
    }

    public function testRunUniqueShouldThrowIfStaleJobIsGiven()
    {
        $root = new Job();
        $root->setId(10);
        $child = new Job();
        $child->setRootJob($root);
        $child->setStoppedAt(new \DateTime());
        $root->addChildJob($child);
        $root->setStatus(Job::STATUS_STALE);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root));
        $jobExtension = $this->createJobExtensionMock();

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $this->expectException(StaleJobRuntimeException::class);
        $this->expectExceptionMessage('Cannot run jobs in status stale, id: "10"');
        $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function () {
                return true;
            }
        );
    }

    public function testCreateDelayedShouldCreateChildJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with('job-name', $this->identicalTo($root))
            ->will($this->returnValue($child));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreCreateDelayed')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostCreateDelayed')
            ->with($child, true);

        $expRunner = null;
        $expJob = null;

        $jobRunner = new JobRunner($jobProcessor, $jobExtension, $root);

        $jobRunner->createDelayed(
            'job-name',
            function (JobRunner $runner, Job $job) use (&$expRunner, &$expJob) {
                $expRunner = $runner;
                $expJob = $job;

                return true;
            }
        );

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expJob, $child);
    }


    public function testRunDelayedShouldCallFailAndRedeliveryAndThrowExceptionIfCallbackThrowException()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob')
            ->with($this->identicalTo($child));
        $jobProcessor->expects($this->never())
            ->method('failChildJob');
        $jobProcessor->expects($this->once())
            ->method('failAndRedeliveryChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->never())
            ->method('onPreCreateDelayed');
        $jobExtension->expects($this->never())
            ->method('onPostCreateDelayed');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) {
                throw new \Exception('test');
            }
        );
    }

    public function testRunDelayedShouldThrowExceptionIfJobWasNotFoundById()
    {
        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue(null));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->never())
            ->method('onPreCreateDelayed');
        $jobExtension->expects($this->never())
            ->method('onPostCreateDelayed');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Job was not found. id: "job-id"');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function () {
            }
        );
    }

    public function testRunDelayedShouldFindJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($child, true);

        $expRunner = null;
        $expJob = null;

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) use (&$expRunner, &$expJob) {
                $expRunner = $runner;
                $expJob = $job;

                return true;
            }
        );

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expJob, $child);
    }

    public function testRunDelayedShouldCancelJobIfRootJobIsInterrupted()
    {
        $root = new Job();
        $root->setInterrupted(true);
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->once())
            ->method('cancelChildJob')
            ->with($this->identicalTo($child));
        $jobProcessor->expects($this->never())
            ->method('failAndRedeliveryChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->never())
            ->method('onPreRunDelayed');
        $jobExtension->expects($this->never())
            ->method('onPostRunDelayed');

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) {
                return true;
            }
        );
    }

    public function testRunDelayedShouldSuccessJobIfCallbackReturnValueIsTrue()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->once())
            ->method('successChildJob')
            ->with($this->identicalTo($child));
        $jobProcessor->expects($this->never())
            ->method('failChildJob');
        $jobProcessor->expects($this->never())
            ->method('failAndRedeliveryChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($child, true);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) {
                return true;
            }
        );
    }

    public function testRunDelayedShouldFailJobIfCallbackReturnValueIsFalse()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob');
        $jobProcessor->expects($this->once())
            ->method('failChildJob')
            ->with($this->identicalTo($child));
        $jobProcessor->expects($this->never())
            ->method('failAndRedeliveryChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($child, false);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) {
                return false;
            }
        );
    }

    public function testRunDelayedShouldNotSuccessJobIfAlreadyStopped()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);
        $child->setStoppedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child));
        $jobProcessor->expects($this->never())
            ->method('successChildJob');
        $jobProcessor->expects($this->never())
            ->method('failChildJob');

        $jobExtension = $this->createJobExtensionMock();
        $jobExtension->expects($this->once())
            ->method('onPreRunDelayed')
            ->with($child);
        $jobExtension->expects($this->once())
            ->method('onPostRunDelayed')
            ->with($child, true);

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $jobRunner->runDelayed(
            'job-id',
            function (JobRunner $runner, Job $job) {
                return true;
            }
        );
    }

    public function testRunDelayedShouldThrowIfStaleJobIsGiven()
    {
        $job = new Job();
        $job->setId(11);
        $job->setStatus(Job::STATUS_STALE);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor->expects($this->once())
            ->method('findJobById')
            ->will($this->returnValue($job));
        $jobExtension = $this->createJobExtensionMock();

        $jobRunner = new JobRunner($jobProcessor, $jobExtension);

        $this->expectException(StaleJobRuntimeException::class);
        $this->expectExceptionMessage('Cannot run jobs in status stale, id: "11"');
        $jobRunner->runDelayed(
            'job-id',
            function () {
                return true;
            }
        );
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobProcessor
     */
    private function createJobProcessorMock()
    {
        return $this->createMock(JobProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    private function createJobExtensionMock()
    {
        return $this->createMock(ExtensionInterface::class);
    }
}
