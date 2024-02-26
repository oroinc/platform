<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Workflow Transition Record
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_workflow_transition_log')]
#[ORM\HasLifecycleCallbacks]
class WorkflowTransitionRecord
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowItem::class, inversedBy: 'transitionRecords')]
    #[ORM\JoinColumn(name: 'workflow_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?WorkflowItem $workflowItem = null;

    #[ORM\Column(name: 'transition', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $transitionName = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class)]
    #[ORM\JoinColumn(name: 'step_from_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?WorkflowStep $stepFrom = null;

    #[ORM\ManyToOne(targetEntity: WorkflowStep::class)]
    #[ORM\JoinColumn(name: 'step_to_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?WorkflowStep $stepTo = null;

    #[ORM\Column(name: 'transition_date', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $transitionDate = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WorkflowStep $stepFrom
     * @return WorkflowTransitionRecord
     */
    public function setStepFrom($stepFrom)
    {
        $this->stepFrom = $stepFrom;
        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getStepFrom()
    {
        return $this->stepFrom;
    }

    /**
     * @param WorkflowStep $stepTo
     * @return WorkflowTransitionRecord
     */
    public function setStepTo($stepTo)
    {
        $this->stepTo = $stepTo;
        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getStepTo()
    {
        return $this->stepTo;
    }

    /**
     * @param string $transitionName
     * @return WorkflowTransitionRecord
     */
    public function setTransitionName($transitionName)
    {
        $this->transitionName = $transitionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransitionName()
    {
        return $this->transitionName;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return WorkflowTransitionRecord
     */
    public function setWorkflowItem($workflowItem)
    {
        $this->workflowItem = $workflowItem;
        return $this;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }

    /**
     * @return \DateTime
     */
    public function getTransitionDate()
    {
        return $this->transitionDate;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->transitionDate = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
