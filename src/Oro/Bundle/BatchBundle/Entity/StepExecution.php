<?php

namespace Oro\Bundle\BatchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\BatchBundle\Exception\RuntimeErrorException;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\ExitStatus;

/**
 * Represents a batch job step execution.
 *
 * @ORM\Table(name="akeneo_batch_step_execution")
 * @ORM\Entity()
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class StepExecution
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="JobExecution", inversedBy="stepExecutions")
     * @ORM\JoinColumn(name="job_execution_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private ?JobExecution $jobExecution;

    /**
     * @ORM\Column(name="step_name", type="string", length=100, nullable=true)
     */
    private ?string $stepName;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    private int $status;

    /**
     * @ORM\Column(name="read_count", type="integer")
     */
    private int $readCount = 0;

    /**
     * @orm\column(name="write_count", type="integer")
     */
    private int $writeCount = 0;

    /**
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    private ?\DateTime $startTime;

    /**
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private ?\DateTime $endTime = null;

    /**
     * @ORM\Column(name="exit_code", type="string", length=255, nullable=true)
     */
    private ?string $exitCode;

    /**
     * @ORM\Column(name="exit_description", type="text", nullable=true)
     */
    private ?string $exitDescription = null;

    /**
     * @ORM\Column(name="terminate_only", type="boolean", nullable=true)
     */
    private bool $terminateOnly = false;

    /**
     * @ORM\Column(name="failure_exceptions", type="array", nullable=true)
     */
    private ?array $failureExceptions;

    /**
     * @ORM\Column(name="errors", type="array")
     */
    private array $errors;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Warning",
     *      mappedBy="stepExecution",
     *      cascade={"persist", "remove"},
     *      fetch="EXTRA_LAZY",
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private Collection $warnings;

    /**
     * @ORM\Column(name="summary", type="array")
     */
    private array $summary = [];

    private ExecutionContext $executionContext;

    private ExitStatus $exitStatus;

    /**
     * @param string $stepName The step to which this execution belongs
     * @param JobExecution $jobExecution The current job execution
     */
    public function __construct(string $stepName, JobExecution $jobExecution)
    {
        $this->stepName = $stepName;
        $this->jobExecution = $jobExecution;
        $jobExecution->addStepExecution($this);
        $this->warnings = new ArrayCollection();
        $this->executionContext = new ExecutionContext();
        $this->setStatus(new BatchStatus(BatchStatus::STARTING));
        $this->setExitStatus(new ExitStatus(ExitStatus::EXECUTING));

        $this->failureExceptions = [];
        $this->errors = [];

        $this->startTime = new \DateTime();
    }

    public function __clone()
    {
        $this->id = null;
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

    /**
     * @return int the current number of items read for this execution
     */
    public function getReadCount(): int
    {
        return $this->readCount;
    }

    /**
     * @param integer $readCount The current number of read items for this execution
     *
     * @return self
     */
    public function setReadCount(int $readCount): self
    {
        $this->readCount = $readCount;

        return $this;
    }

    public function incrementReadCount(): self
    {
        $this->readCount++;

        return $this;
    }

    /**
     * @return int The current number of items written for this execution
     */
    public function getWriteCount(): int
    {
        return $this->writeCount;
    }

    /**
     * @param integer $writeCount the current number of written items for this execution
     *
     * @return self
     */
    public function setWriteCount(int $writeCount): self
    {
        $this->writeCount = $writeCount;

        return $this;
    }

    /**
     * @return int The current number of items filtered out of this execution
     */
    public function getFilterCount(): int
    {
        return $this->readCount - $this->writeCount;
    }

    /**
     * @return bool Indicate that an execution should halt
     */
    public function isTerminateOnly(): bool
    {
        return $this->terminateOnly;
    }

    /**
     * Sets a flag that will signal to an execution environment that this
     * execution (and its surrounding job) wishes to exit.
     */
    public function setTerminateOnly(): self
    {
        $this->terminateOnly = true;

        return $this;
    }

    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): self
    {
        $this->startTime = $startTime;

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

    public function getStepName(): string
    {
        return $this->stepName;
    }

    public function setExitStatus(ExitStatus $exitStatus): self
    {
        $this->exitStatus = $exitStatus;
        $this->exitCode = $exitStatus->getExitCode();
        $this->exitDescription = $exitStatus->getExitDescription();

        return $this;
    }

    public function getExitStatus(): ExitStatus
    {
        return $this->exitStatus;
    }

    /**
     * @return JobExecution The job execution that was used to start this step execution.
     */
    public function getJobExecution(): JobExecution
    {
        return $this->jobExecution;
    }

    public function getFailureExceptions(): ?array
    {
        return $this->failureExceptions;
    }

    public function addFailureException(\Exception $e): self
    {
        if (!$this->failureExceptions) {
            $this->failureExceptions = [];
        }

        $this->failureExceptions[] = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'messageParameters' => $e instanceof RuntimeErrorException ? $e->getMessageParameters() : [],
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString(),
        ];

        return $this;
    }

    public function getFailureExceptionMessages(): string
    {
        return implode(' ', array_column((array) $this->failureExceptions, 'message'));
    }

    public function addError(string $message): self
    {
        $this->errors[] = $message;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $name
     * @param string $reason
     * @param array $reasonParameters
     * @param array|object $item
     *
     * @return self
     */
    public function addWarning(string $name, string $reason, array $reasonParameters, $item): self
    {
        $element = $this->stepName;
        if (strpos($element, '.')) {
            $element = substr($element, 0, strpos($element, '.'));
        }

        if (\is_object($item)) {
            $item = [
                'class' => ClassUtils::getClass($item),
                'id' => method_exists($item, 'getId') ? $item->getId() : '[unknown]',
                'string' => method_exists($item, '__toString') ? (string)$item : '[unknown]',
            ];
        }

        $this->warnings->add(
            new Warning(
                $this,
                sprintf('%s.steps.%s.title', $element, $name),
                $reason,
                $reasonParameters,
                $item
            )
        );

        return $this;
    }

    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    public function incrementSummaryInfo(string $key, int $increment = 1): self
    {
        if (!isset($this->summary[$key])) {
            $this->summary[$key] = $increment;
        } else {
            $this->summary[$key] += $increment;
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return string|array
     */
    public function getSummaryInfo(string $key)
    {
        return $this->summary[$key];
    }

    public function setSummary(array $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }

    public function __toString(): string
    {
        $summary = 'id=%d, name=[%s], status=[%s], exitCode=[%s], exitDescription=[%s]';

        return sprintf(
            $summary,
            $this->id,
            $this->stepName,
            $this->status,
            $this->exitCode,
            $this->exitDescription
        );
    }
}
