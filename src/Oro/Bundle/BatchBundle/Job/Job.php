<?php

namespace Oro\Bundle\BatchBundle\Job;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Event\EventInterface;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\Exception\JobInterruptedException;
use Oro\Bundle\BatchBundle\Step\StepInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Represents a batch job.
 */
class Job implements JobInterface
{
    protected string $name;

    protected EventDispatcherInterface $eventDispatcher;

    protected JobRepositoryInterface $jobRepository;

    protected array $steps = [];

    /**
     * Convenient constructor which immediately adds a name (which is mandatory)
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->steps = [];
    }

    /**
     * Get the job's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name property
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the event dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Return all the steps
     *
     * @return StepInterface[]
     */
    public function getSteps(): array
    {
        return (array)$this->steps;
    }

    /**
     * Public setter for the steps in this job. Overrides any calls to
     * addStep(Step).
     *
     * @param StepInterface[] $steps the steps to execute
     *
     * @return self
     */
    public function setSteps(array $steps): self
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * Retrieve the step with the given name. If there is no Step with the given
     * name, then return null.
     */
    public function getStep(string $stepName): ?StepInterface
    {
        foreach ($this->steps as $step) {
            if ($step->getName() === $stepName) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Retrieve the step names.
     *
     * @return string[] the step names
     */
    public function getStepNames(): array
    {
        $names = [];
        foreach ($this->steps as $step) {
            $names[] = $step->getName();
        }

        return $names;
    }

    public function addStep(StepInterface $step): self
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Public setter for the {@link JobRepositoryInterface} that is needed to manage the
     * state of the batch meta domain (jobs, steps, executions) during the life
     * of a job.
     */
    public function setJobRepository(JobRepositoryInterface $jobRepository): void
    {
        $this->jobRepository = $jobRepository;
    }

    /**
     * Public getter for the {@link JobRepositoryInterface} that is needed to manage the
     * state of the batch meta domain (jobs, steps, executions) during the life
     * of a job.
     */
    public function getJobRepository(): JobRepositoryInterface
    {
        return $this->jobRepository;
    }

    /**
     * Get the steps configuration
     */
    public function getConfiguration(): array
    {
        $result = [];
        foreach ($this->steps as $step) {
            foreach ($step->getConfiguration() as $key => $value) {
                if (!isset($result[$key]) || $value) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Set the steps configuration
     */
    public function setConfiguration(array $config): void
    {
        foreach ($this->steps as $step) {
            $step->setConfiguration($config);
        }
    }

    /**
     * To string
     */
    public function __toString(): string
    {
        return get_class($this) . ': [name=' . $this->name . ']';
    }

    /**
     * Run the specified job, handling all listener and repository calls, and
     * delegating the actual processing to {@link #doExecute(JobExecution)}.
     *
     * @see Job::execute(JobExecution)
     */
    final public function execute(JobExecution $jobExecution): void
    {
        $this->dispatchJobExecutionEvent(EventInterface::BEFORE_JOB_EXECUTION, $jobExecution);

        try {
            if ($jobExecution->getStatus()->getValue() !== BatchStatus::STOPPING) {
                $jobExecution->setStartTime(new \DateTime());
                $this->updateStatus($jobExecution, BatchStatus::STARTED);
                $this->jobRepository->updateJobExecution($jobExecution);

                $this->doExecute($jobExecution);
            } else {
                // The job was already stopped before we even got this far. Deal
                // with it in the same way as any other interruption.
                $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPED));
                $jobExecution->setExitStatus(new ExitStatus(ExitStatus::COMPLETED));
                $this->jobRepository->updateJobExecution($jobExecution);

                $this->dispatchJobExecutionEvent(EventInterface::JOB_EXECUTION_STOPPED, $jobExecution);
            }
        } catch (JobInterruptedException $e) {
            $jobExecution->setExitStatus($this->getDefaultExitStatusForFailure($e));
            $jobExecution->setStatus(
                new BatchStatus(
                    BatchStatus::max(BatchStatus::STOPPED, $e->getStatus()->getValue())
                )
            );
            $jobExecution->addFailureException($e);
            $this->jobRepository->updateJobExecution($jobExecution);

            $this->dispatchJobExecutionEvent(EventInterface::JOB_EXECUTION_INTERRUPTED, $jobExecution);
        } catch (\Exception $e) {
            $jobExecution->setExitStatus($this->getDefaultExitStatusForFailure($e));
            $jobExecution->setStatus(new BatchStatus(BatchStatus::FAILED));
            $jobExecution->addFailureException($e);
            $this->jobRepository->updateJobExecution($jobExecution);

            $this->dispatchJobExecutionEvent(EventInterface::JOB_EXECUTION_FATAL_ERROR, $jobExecution);
        }

        if (($jobExecution->getStatus()->getValue() <= BatchStatus::STOPPED)
            && (count($jobExecution->getStepExecutions()) === 0)
        ) {
            /* @var ExitStatus */
            $exitStatus = $jobExecution->getExitStatus();
            $noopExitStatus = new ExitStatus(ExitStatus::NOOP);
            $noopExitStatus->addExitDescription("All steps already completed or no steps configured for this job.");
            $jobExecution->setExitStatus($exitStatus->logicalAnd($noopExitStatus));
            $this->jobRepository->updateJobExecution($jobExecution);
        }

        $this->dispatchJobExecutionEvent(EventInterface::AFTER_JOB_EXECUTION, $jobExecution);

        $jobExecution->setEndTime(new \DateTime());
        $this->jobRepository->updateJobExecution($jobExecution);
    }

    /**
     * Handler of steps sequentially as provided, checking each one for success
     * before moving to the next. Returns the last {@link StepExecution}
     * successfully processed if it exists, and null if none were processed.
     *
     * @param JobExecution $jobExecution the current {@link JobExecution}
     *
     * @throws JobInterruptedException
     */
    protected function doExecute(JobExecution $jobExecution): void
    {
        /* @var StepExecution $stepExecution */
        $stepExecution = null;

        foreach ($this->steps as $step) {
            $stepExecution = $this->handleStep($step, $jobExecution);
            $this->jobRepository->updateStepExecution($stepExecution);

            if ($stepExecution->getStatus()->getValue() !== BatchStatus::COMPLETED) {
                // Terminate the job if a step fails
                break;
            }
        }

        // Update the job status to be the same as the last step
        if ($stepExecution !== null) {
            $this->dispatchJobExecutionEvent(EventInterface::BEFORE_JOB_STATUS_UPGRADE, $jobExecution);

            $jobExecution->upgradeStatus($stepExecution->getStatus()->getValue());
            $jobExecution->setExitStatus($stepExecution->getExitStatus());
            $this->jobRepository->updateJobExecution($jobExecution);
        }
    }

    /**
     * Handle a step and return the execution for it.
     *
     * @param StepInterface $step Step
     * @param JobExecution $jobExecution Job execution
     *
     * @return StepExecution
     *
     * @throws JobInterruptedException
     */
    public function handleStep(StepInterface $step, JobExecution $jobExecution): StepExecution
    {
        if ($jobExecution->isStopping()) {
            throw new JobInterruptedException("JobExecution interrupted.");
        }

        $stepExecution = $jobExecution->createStepExecution($step->getName());

        try {
            $step->setJobRepository($this->jobRepository);
            $step->execute($stepExecution);
        } catch (JobInterruptedException $e) {
            $stepExecution->setStatus(new BatchStatus(BatchStatus::STOPPING));
            $this->jobRepository->updateStepExecution($stepExecution);
            throw $e;
        }

        if (in_array($stepExecution->getStatus()->getValue(), [BatchStatus::STOPPING, BatchStatus::STOPPED], false)) {
            $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPING));
            $this->jobRepository->updateJobExecution($jobExecution);
            throw new JobInterruptedException("Job interrupted by step execution");
        }

        return $stepExecution;
    }

    /**
     * Trigger event linked to JobExecution
     *
     * @param string $eventName Name of the event
     * @param JobExecution $jobExecution Object to store job execution
     */
    private function dispatchJobExecutionEvent(string $eventName, JobExecution $jobExecution): void
    {
        $event = new JobExecutionEvent($jobExecution);
        $this->dispatch($eventName, $event);
    }

    /**
     * Generic batch event dispatcher
     *
     * @param string $eventName Name of the event
     * @param Event $event Event object
     */
    private function dispatch(string $eventName, Event $event): void
    {
        $this->eventDispatcher->dispatch($event, $eventName);
    }

    /**
     * Default mapping from throwable to {@link ExitStatus}. Clients can modify the exit code using a
     * {@link StepExecutionListener}.
     *
     * @param \Exception $e the cause of the failure
     *
     * @return ExitStatus an {@link ExitStatus}
     */
    private function getDefaultExitStatusForFailure(\Exception $e): ExitStatus
    {
        if ($e instanceof JobInterruptedException || $e->getPrevious() instanceof JobInterruptedException) {
            $exitStatus = new ExitStatus(ExitStatus::STOPPED);
            $exitStatus->addExitDescription(get_class(new JobInterruptedException()));
        } else {
            $exitStatus = new ExitStatus(ExitStatus::FAILED);
            $exitStatus->addExitDescription($e);
        }

        return $exitStatus;
    }

    /**
     * Default mapping from throwable to {@link ExitStatus}. Clients can modify the exit code using a
     * {@link StepExecutionListener}.
     *
     * @param JobExecution $jobExecution Execution of the job
     * @param string $status Status of the execution
     */
    private function updateStatus(JobExecution $jobExecution, string $status): void
    {
        $jobExecution->setStatus(new BatchStatus($status));
    }
}
