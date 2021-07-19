<?php

namespace Oro\Bundle\BatchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a Warning raised during step execution
 *
 * @ORM\Table(name="akeneo_batch_warning")
 * @ORM\Entity()
 */
class Warning
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="StepExecution", inversedBy="warnings")
     * @ORM\JoinColumn(name="step_execution_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private StepExecution $stepExecution;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private string $name;

    /**
     * @ORM\Column(name="reason", type="text", nullable=true)
     */
    private string $reason;

    /**
     * @ORM\Column(name="reason_parameters", type="array", nullable=false)
     */
    private array $reasonParameters;

    /**
     * @ORM\Column(name="item", type="array", nullable=false)
     */
    private array $item;

    public function __construct(
        StepExecution $stepExecution,
        string $name,
        string $reason,
        array $reasonParameters,
        array $item
    ) {
        $this->stepExecution = $stepExecution;
        $this->name = $name;
        $this->reason = $reason;
        $this->reasonParameters = $reasonParameters;
        $this->item = $item;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStepExecution(): StepExecution
    {
        return $this->stepExecution;
    }

    public function setStepExecution(StepExecution $stepExecution): self
    {
        $this->stepExecution = $stepExecution;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getReasonParameters(): array
    {
        return $this->reasonParameters;
    }

    public function setReasonParameters(array $reasonParameters): self
    {
        $this->reasonParameters = $reasonParameters;

        return $this;
    }

    public function getItem(): array
    {
        return $this->item;
    }

    public function setItem(array $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'reason' => $this->reason,
            'reasonParameters' => $this->reasonParameters,
            'item' => $this->item,
        ];
    }
}
