<?php

namespace Oro\Bundle\BatchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\BatchBundle\Job\Job;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents a batch job instance.
 *
 * @ORM\Table(name="akeneo_batch_job_instance")
 * @ORM\Entity()
 * @UniqueEntity(fields="code", message="This code is already taken")
 */
class JobInstance
{
    public const STATUS_READY = 0;
    public const STATUS_DRAFT = 1;
    public const STATUS_IN_PROGRESS = 2;

    public const TYPE_IMPORT = 'import';
    public const TYPE_EXPORT = 'export';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="code", type="string", length=100, unique=true)
     */
    protected ?string $code = null;

    /**
     * @ORM\Column(nullable=true)
     */
    protected ?string $label = null;

    /**
     * @ORM\Column(name="alias", type="string", length=50)
     */
    protected ?string $alias;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    protected int $status = self::STATUS_READY;

    /**
     * @ORM\Column(name="connector", type="string")
     */
    protected ?string $connector;

    /**
     * JobInstance type export or import
     *
     * @ORM\Column(name="type", type="string")
     */
    protected ?string $type;

    /**
     * @ORM\Column(type="array")
     */
    protected array $rawConfiguration = [];

    /**
     * @ORM\OneToMany(
     *      targetEntity="JobExecution",
     *      mappedBy="jobInstance",
     *      cascade={"remove"},
     *      orphanRemoval=true
     * )
     */
    protected Collection $jobExecutions;

    protected ?Job $job = null;

    public function __construct(?string $connector = null, ?string $type = null, ?string $alias = null)
    {
        $this->connector = $connector;
        $this->type = $type;
        $this->alias = $alias;
        $this->jobExecutions = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;

        if ($this->jobExecutions) {
            $this->jobExecutions = clone $this->jobExecutions;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getConnector(): ?string
    {
        return $this->connector;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setRawConfiguration(array $configuration): self
    {
        $this->rawConfiguration = $configuration;

        return $this;
    }

    public function getRawConfiguration(): array
    {
        return $this->rawConfiguration;
    }

    public function setJob(Job $job): self
    {
        $this->job = $job;

        if ($job) {
            $jobConfiguration = $job->getConfiguration();
            if (is_array($this->rawConfiguration) && count($this->rawConfiguration) > 0) {
                $this->rawConfiguration = array_merge($this->rawConfiguration, $jobConfiguration);
            } else {
                $this->rawConfiguration = $jobConfiguration;
            }
        }

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function getJobExecutions(): Collection
    {
        return $this->jobExecutions;
    }

    public function addJobExecution(JobExecution $jobExecution): self
    {
        $this->jobExecutions->add($jobExecution);

        return $this;
    }

    public function removeJobExecution(JobExecution $jobExecution): self
    {
        $this->jobExecutions->removeElement($jobExecution);

        return $this;
    }

    /**
     * Sets job alias.
     * Throws logic exception if alias property is already set.
     *
     * @throws \LogicException
     */
    public function setAlias(string $alias): self
    {
        if ($this->alias !== null) {
            throw new \LogicException('Alias already set in JobInstance');
        }

        $this->alias = $alias;

        return $this;
    }

    /**
     * Sets job connector.
     * Throws exception if connector property is already set.
     *
     * @throws \LogicException
     */
    public function setConnector(string $connector): self
    {
        if ($this->connector !== null) {
            throw new \LogicException('Connector already set in JobInstance');
        }

        $this->connector = $connector;

        return $this;
    }
}
