<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
* Entity that represents Base Transition Trigger
*
*/
#[ORM\Entity]
#[ORM\Table('oro_workflow_trans_trigger')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['cron' => TransitionCronTrigger::class, 'event' => TransitionEventTrigger::class])]
#[Config(mode: 'hidden')]
abstract class BaseTransitionTrigger
{
    use DatesAwareTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Whether transition should be queued or done immediately
     *
     * @var boolean
     */
    #[ORM\Column(name: 'queued', type: Types::BOOLEAN)]
    protected ?bool $queued = true;

    #[ORM\Column(name: 'transition_name', type: Types::STRING, length: 255)]
    protected ?string $transitionName = null;

    #[ORM\ManyToOne(targetEntity: WorkflowDefinition::class)]
    #[ORM\JoinColumn(name: 'workflow_name', referencedColumnName: 'name', nullable: false, onDelete: 'CASCADE')]
    protected ?WorkflowDefinition $workflowDefinition = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTransitionName()
    {
        return $this->transitionName;
    }

    /**
     * @param string $transitionName
     * @return $this
     */
    public function setTransitionName($transitionName)
    {
        $this->transitionName = $transitionName;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isQueued()
    {
        return $this->queued;
    }

    /**
     * @param boolean $queued
     * @return $this
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;

        return $this;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return $this
     */
    public function setWorkflowDefinition(WorkflowDefinition $definition)
    {
        $this->workflowDefinition = $definition;

        return $this;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getWorkflowDefinition()
    {
        return $this->workflowDefinition;
    }

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->getWorkflowDefinition() ? $this->getWorkflowDefinition()->getName() : null;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->preUpdate();
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    protected function importMainData(BaseTransitionTrigger $trigger)
    {
        $this->setQueued($trigger->isQueued())
            ->setTransitionName($trigger->getTransitionName())
            ->setWorkflowDefinition($trigger->getWorkflowDefinition());
    }

    /**
     * @param BaseTransitionTrigger $trigger
     * @return bool
     */
    public function isEqualTo(BaseTransitionTrigger $trigger)
    {
        $expectedWorkflowName = $this->workflowDefinition ? $this->workflowDefinition->getName() : null;
        $actualWorkflowName = $trigger->workflowDefinition ? $trigger->workflowDefinition->getName() : null;

        return $expectedWorkflowName === $actualWorkflowName
            && $this->queued === $trigger->isQueued()
            && $this->transitionName === $trigger->getTransitionName()
            && $this->isEqualAdditionalFields($trigger);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->getWorkflowDefinition() ? $this->getWorkflowDefinition()->getRelatedEntity() : null;
    }

    /**
     * Compare additional fields of triggers
     *
     * @param BaseTransitionTrigger $trigger
     * @return bool
     */
    abstract protected function isEqualAdditionalFields(BaseTransitionTrigger $trigger);
}
