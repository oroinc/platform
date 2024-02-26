<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;

/**
 * Represents a workflow step entity.
 */
#[ORM\Entity(repositoryClass: WorkflowStepRepository::class)]
#[ORM\Table(name: 'oro_workflow_step')]
#[ORM\Index(columns: ['name'], name: 'oro_workflow_step_name_idx')]
#[ORM\UniqueConstraint(name: 'oro_workflow_step_unique_idx', columns: ['workflow_name', 'name'])]
#[Config(
    defaultValues: [
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class WorkflowStep
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255)]
    protected ?string $label = null;

    #[ORM\Column(name: 'step_order', type: Types::INTEGER)]
    protected ?int $stepOrder = 0;

    #[ORM\Column(name: 'is_final', type: Types::BOOLEAN)]
    protected ?bool $final = false;

    #[ORM\ManyToOne(targetEntity: WorkflowDefinition::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'workflow_name', referencedColumnName: 'name', onDelete: 'CASCADE')]
    protected ?WorkflowDefinition $definition = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return WorkflowStep
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $label
     * @return WorkflowStep
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param int $order
     * @return WorkflowStep
     */
    public function setStepOrder($order)
    {
        $this->stepOrder = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getStepOrder()
    {
        return $this->stepOrder;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowStep
     */
    public function setDefinition(WorkflowDefinition $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param boolean $final
     * @return WorkflowStep
     */
    public function setFinal($final)
    {
        $this->final = $final;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * @param WorkflowStep $workflowStep
     * @return WorkflowStep
     */
    public function import(WorkflowStep $workflowStep)
    {
        $this->setName($workflowStep->getName())
            ->setLabel($workflowStep->getLabel())
            ->setStepOrder($workflowStep->getStepOrder())
            ->setFinal($workflowStep->isFinal());

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->label;
    }
}
