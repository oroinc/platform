<?php

namespace Oro\Bundle\BatchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\BatchBundle\Exception\RuntimeErrorException;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;

/**
 * Represents a batch job execution.
 *
 * @ORM\Table(name="akeneo_batch_job_execution")
 * @ORM\Entity()
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class JobExecution
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToMany(
     *      targetEntity="StepExecution",
     *      mappedBy="jobExecution",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     */
    private Collection $stepExecutions;

    /**
     * @ORM\ManyToOne(targetEntity="JobInstance", inversedBy="jobExecutions")
     * @ORM\JoinColumn(name="job_instance_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private ?JobInstance $jobInstance = null;

    /**
     * @var int|null Process Identifier
     *
     * @ORM\Column(name="pid", type="integer", nullable=true)
     */
    private ?int $pid = null;

    /**
     * @var string|null The user who launched the job
     *
     * @ORM\Column(name="`user`", type="string", nullable=true)
     */
    private ?string $user = null;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    private int $status = BatchStatus::UNKNOWN;

    /**
     * @ORM\Column(name="create_time", type="datetime", nullable=true)
     */
    private \DateTime $createTime;

    /**
     * @ORM\Column(name="updated_time", type="datetime", nullable=true)
     */
    private ?\DateTime $updatedTime = null;

    /**
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    private ?\DateTime $startTime = null;

    /**
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private ?\DateTime $endTime = null;

    /**
     * @ORM\Column(name="exit_code", type="string", length=255, nullable=true)
     */
    private ?string $exitCode = null;

    /**
     * @ORM\Column(name="exit_description", type="text", nullable=true)
     */
    private ?string $exitDescription = null;

    /**
     * @ORM\Column(name="failure_exceptions", type="array", nullable=true)
     */
    private ?array $failureExceptions;

    /**
     * @ORM\Column(name="log_file", type="string", length=255, nullable=true)
     */
    private ?string $logFile = null;

    private ExecutionContext $executionContext;

    private ?ExitStatus $exitStatus = null;

    public function __construct()
    {
        $this->setStatus(new BatchStatus(BatchStatus::STARTING));
        $this->setExitStatus(new ExitStatus(ExitStatus::UNKNOWN));

        $this->executionContext = new ExecutionContext();
        $this->stepExecutions = new ArrayCollection();
        $this->createTime = new \DateTime();
        $this->failureExceptions = [];
    }

    public function __clone()
    {
        $this->id = null;

        if ($this->stepExecutions) {
            $this->stepExecutions = clone $this->stepExecutions;
        }

        if ($this->executionContext) {
            $this->executionContext = clone $this->executionContext;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExecutionContext(): ExecutionContext
    {
        return $this->executionContext;
    }

    public function setExecutionContext(ExecutionContext $executionContext): self
    {
        $this->executionContext = $executionContext;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getCreateTime(): \DateTime
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTime $createTime): self
    {
        $this->createTime = $createTime;

        return $this;
    }

    public function getUpdatedTime(): ?\DateTime
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(\DateTime $updatedTime): self
    {
        $this->updatedTime = $updatedTime;

        return $this;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(int $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): BatchStatus
    {
        return new BatchStatus($this->status);
    }

    public function setStatus(BatchStatus $status): self
    {
        $this->status = $status->getValue();

        return $this;
    }

    /**
     * Upgrade the status field if the provided value is greater than the
     * existing one. Clients using this method to set the status can be sure
     * that they don't overwrite a failed status with an successful one.
     *
     * @param int $status the new status value
     *
     * @return self
     */
    public function upgradeStatus(int $status): self
    {
        $newBatchStatus = $this->getStatus();
        $newBatchStatus->upgradeTo($status);
        $this->setStatus($newBatchStatus);

        return $this;
    }

    public function setExitStatus(ExitStatus $exitStatus): self
    {
        $this->exitStatus = $exitStatus;
        $this->exitCode = $exitStatus->getExitCode();
        $this->exitDescription = $exitStatus->getExitDescription();

        return $this;
    }

    public function getExitStatus(): ?ExitStatus
    {
        if ($this->exitStatus === null && $this->exitCode !== null) {
            $this->exitStatus = new ExitStatus($this->exitCode);
        }

        return $this->exitStatus;
    }

    /**
     * @return Collection<StepExecution>
     */
    public function getStepExecutions(): Collection
    {
        return $this->stepExecutions;
    }

    /**
     * Register a step execution with the current job execution.
     *
     * @param string $stepName the name of the step the new execution is associated with
     *
     * @return StepExecution
     */
    public function createStepExecution(string $stepName): StepExecution
    {
        return new StepExecution($stepName, $this);
    }

    public function addStepExecution(StepExecution $stepExecution): self
    {
        $this->stepExecutions->add($stepExecution);

        return $this;
    }

    /**
     * Test if this JobExecution indicates that it is running. It should
     * be noted that this does not necessarily mean that it has been persisted
     * as such yet.
     *
     * @return bool if the end time is null
     */
    public function isRunning(): bool
    {
        return $this->getStatus()->isRunning();
    }

    /**
     * Test if this JobExecution indicates that it has been signalled to
     * stop.
     *
     * @return bool if the status is BatchStatus::STOPPING
     */
    public function isStopping(): bool
    {
        return $this->status === BatchStatus::STOPPING;
    }

    /**
     * Signal the JobExecution to stop. Iterates through the associated
     * StepExecution, calling StepExecution::setTerminateOnly().
     */
    public function stop(): self
    {
        /** @var StepExecution $stepExecution */
        foreach ($this->stepExecutions as $stepExecution) {
            $stepExecution->setTerminateOnly();
        }
        $this->status = BatchStatus::STOPPING;

        return $this;
    }

    public function getFailureExceptions(): ?array
    {
        return $this->failureExceptions;
    }

    public function addFailureException(\Exception $e): self
    {
        $this->failureExceptions[] = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'messageParameters' => $e instanceof RuntimeErrorException ? $e->getMessageParameters() : [],
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString(),
        ];

        return $this;
    }

    /**
     * Return all failure causing exceptions for this JobExecution, including
     * step executions.
     *
     * @return array Containing all exceptions causing failure for this JobExecution.
     */
    public function getAllFailureExceptions(): array
    {
        $allExceptions[] = $this->failureExceptions;

        /** @var StepExecution $stepExecution */
        foreach ($this->stepExecutions as $stepExecution) {
            $allExceptions[] = $stepExecution->getFailureExceptions();
        }

        return array_merge(...$allExceptions);
    }

    public function setJobInstance(JobInstance $jobInstance): self
    {
        $this->jobInstance = $jobInstance;
        $this->jobInstance->addJobExecution($this);

        return $this;
    }

    public function getJobInstance(): ?JobInstance
    {
        return $this->jobInstance;
    }

    public function getLabel(): string
    {
        return $this->jobInstance->getLabel();
    }

    public function setLogFile(string $logFile): self
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function __toString(): string
    {
        $startTime = $this->formatDate($this->startTime);
        $endTime = $this->formatDate($this->endTime);
        $updatedTime = $this->formatDate($this->updatedTime);
        $jobInstanceCode = $this->jobInstance !== null ? $this->jobInstance->getCode() : '';

        $message = "startTime=%s, endTime=%s, updatedTime=%s, status=%d, exitStatus=%s, exitDescription=[%s], job=[%s]";

        return sprintf(
            $message,
            $startTime,
            $endTime,
            $updatedTime,
            $this->status,
            $this->exitStatus,
            $this->exitDescription,
            $jobInstanceCode
        );
    }

    /**
     * Format a date or return empty string if null
     *
     * @param \DateTimeInterface|null $date
     * @param string $format
     *
     * @return string
     */
    private function formatDate(\DateTimeInterface $date = null, $format = \DateTime::ATOM): string
    {
        return $date !== null ? $date->format($format) : '';
    }
}
