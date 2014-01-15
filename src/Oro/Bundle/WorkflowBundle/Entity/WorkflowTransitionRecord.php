<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_workflow_transition_log")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class WorkflowTransitionRecord
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WorkflowItem", inversedBy="transitionRecords")
     * @ORM\JoinColumn(name="workflow_item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $workflowItem;

    /**
     * @var string
     *
     * @ORM\Column(name="transition", type="string", length=255, nullable=true)
     */
    protected $transitionName;

    /**
     * @var string
     *
     * @ORM\Column(name="step_from", type="string", length=255, nullable=true)
     */
    protected $stepFromName;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="step_from_id", referencedColumnName="id")
     */
    protected $stepFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="step_to", type="string", length=255)
     */
    protected $stepToName;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="step_to_id", referencedColumnName="id")
     */
    protected $stepTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transition_date", type="datetime")
     */
    protected $transitionDate;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $stepFromName
     * @return WorkflowTransitionRecord
     */
    public function setStepFromName($stepFromName)
    {
        $this->stepFromName = $stepFromName;
        return $this;
    }

    /**
     * @return string
     */
    public function getStepFromName()
    {
        return $this->stepFromName;
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
     * @param string $stepToName
     * @return WorkflowTransitionRecord
     */
    public function setStepToName($stepToName)
    {
        $this->stepToName = $stepToName;
        return $this;
    }

    /**
     * @return string
     */
    public function getStepToName()
    {
        return $this->stepToName;
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
     * @param mixed $workflowItem
     * @return WorkflowTransitionRecord
     */
    public function setWorkflowItem($workflowItem)
    {
        $this->workflowItem = $workflowItem;
        return $this;
    }

    /**
     * @return mixed
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

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->transitionDate = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
