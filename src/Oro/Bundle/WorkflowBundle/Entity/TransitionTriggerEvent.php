<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TransitionTriggerEvent extends AbstractTransitionTrigger
{
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';

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
     * @ORM\Column(name="require", type="text", length=1024, nullable=true)
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
     * @return string
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
     * @return string
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
     * @param TransitionTriggerEvent $trigger
     * @return $this
     */
    public function import(TransitionTriggerEvent $trigger)
    {
        $this->setEvent($trigger->getEvent())
            ->setField($trigger->getField());

        parent::importData($trigger);

        return $this;
    }
}
