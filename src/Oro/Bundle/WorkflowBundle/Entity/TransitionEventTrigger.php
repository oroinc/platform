<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository")
 */
class TransitionEventTrigger extends AbstractTransitionTrigger implements EventTriggerInterface
{
    /**
     * Entity from event
     *
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected $entityClass;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=255)
     */
    protected $event;

    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=255, nullable=true)
     */
    protected $field;

    /**
     * @var string
     *
     * @ORM\Column(name="`require`", type="text", length=1024, nullable=true)
     */
    protected $require;

    /**
     * Expression Language condition
     *
     * @var string
     *
     * @ORM\Column(name="relation", type="text", length=1024, nullable=true)
     */
    protected $relation;

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        if ($this->entityClass) {
            return $this->entityClass;
        }

        if ($this->getWorkflowDefinition()) {
            return $this->getWorkflowDefinition()->getRelatedEntity();
        }

        return null;
    }

    /**
     * @param string $entityClass
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $event
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequire()
    {
        return $this->require;
    }

    /**
     * @param string $require
     * @return $this
     */
    public function setRequire($require)
    {
        $this->require = $require;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @param string $relation
     * @return $this
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * @return array
     */
    public static function getAllowedEvents()
    {
        return [self::EVENT_CREATE, self::EVENT_UPDATE, self::EVENT_DELETE];
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @return $this
     */
    public function import(TransitionEventTrigger $trigger)
    {
        $this->importMainData($trigger);

        $this->setEvent($trigger->getEvent())
            ->setEntityClass($trigger->getEntityClass())
            ->setField($trigger->getField())
            ->setRequire($trigger->getRequire())
            ->setRelation($trigger->getRelation());

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            'event: [%s:%s](on:%s%s)%s%s:%s',
            $this->workflowDefinition ? $this->workflowDefinition->getName() : 'null',
            $this->transitionName,
            $this->event,
            $this->field ? '[' . $this->field . ']' : '',
            $this->relation ? ':=>' . $this->relation : '',
            $this->require ? ':expr(' . $this->require . ')' : '',
            $this->queued ? 'MQ' : 'RUNTIME'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function isEqualAdditionalFields(AbstractTransitionTrigger $trigger)
    {
        return $trigger instanceof static
            && $this->entityClass === $trigger->getEntityClass()
            && $this->event === $trigger->getEvent()
            && $this->field === $trigger->getField()
            && $this->relation === $trigger->getRelation()
            && $this->require === $trigger->getRequire();
    }
}
