<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;

/**
 * Represents a transition that is triggered by an event,
 * e.g. when an entity is created or a field value is changed.
 */
#[ORM\Entity(repositoryClass: TransitionEventTriggerRepository::class)]
class TransitionEventTrigger extends BaseTransitionTrigger implements EventTriggerInterface
{
    /**
     * Entity from event
     */
    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'event', type: Types::STRING, length: 255)]
    protected ?string $event = null;

    #[ORM\Column(name: 'field', type: Types::STRING, length: 150, nullable: true)]
    protected ?string $field = null;

    #[ORM\Column(name: '`require`', type: Types::TEXT, length: 1024, nullable: true)]
    protected ?string $require = null;

    /**
     * Expression Language condition
     */
    #[ORM\Column(name: 'relation', type: Types::TEXT, length: 1024, nullable: true)]
    protected ?string $relation = null;

    #[\Override]
    public function getEntityClass()
    {
        if ($this->entityClass) {
            return $this->entityClass;
        }

        return parent::getEntityClass();
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

    #[\Override]
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

    #[\Override]
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
    #[\Override]
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

    #[\Override]
    protected function isEqualAdditionalFields(BaseTransitionTrigger $trigger)
    {
        return $trigger instanceof static
            && $this->entityClass === $trigger->getEntityClass()
            && $this->event === $trigger->getEvent()
            && $this->field === $trigger->getField()
            && $this->relation === $trigger->getRelation()
            && $this->require === $trigger->getRequire();
    }
}
