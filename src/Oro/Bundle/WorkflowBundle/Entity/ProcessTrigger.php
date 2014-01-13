<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *  "oro_process_trigger",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="process_trigger_unique_idx",
 *          columns={"event", "field", "time_shift", "definition_name"}
 *      )
 *  }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProcessTrigger
{
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     * Number of seconds before process must be triggered
     *
     * @var integer
     *
     * @ORM\Column(name="time_shift", type="integer", nullable=true)
     */
    protected $timeShift;

    /**
     * @var ProcessDefinition
     *
     * @ORM\ManyToOne(targetEntity="ProcessDefinition")
     * @ORM\JoinColumn(name="definition_name", referencedColumnName="name", onDelete="CASCADE")
     */
    protected $definition;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $event
     * @return ProcessTrigger
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $field
     * @return ProcessTrigger
     */
    public function setField($field)
    {
        $this->field = $field;

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
     * @param integer $timeShift
     * @return ProcessTrigger
     */
    public function setTimeShift($timeShift)
    {
        $this->timeShift = $timeShift;

        return $this;
    }

    /**
     * @return integer
     */
    public function getTimeShift()
    {
        return $this->timeShift;
    }

    /**
     * @param \DateInterval $timeShiftInterval
     * @return ProcessTrigger
     */
    public function setTimeShiftInterval($timeShiftInterval)
    {
        if (null !== $timeShiftInterval) {
            $this->timeShift = self::convertDateIntervalToSeconds($timeShiftInterval);
        } else {
            $this->timeShift = null;
        }

        return $this;
    }

    /**
     * @return \DateInterval
     */
    public function getTimeShiftInterval()
    {
        if (null === $this->timeShift) {
            return null;
        }

        return self::convertSecondsToDateInterval($this->timeShift);
    }

    /**
     * @param ProcessDefinition $definition
     * @return ProcessTrigger
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return ProcessDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param \DateTime $createdAt
     * @return ProcessTrigger
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return ProcessTrigger
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->preUpdate();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param \DateInterval $interval
     * @return int
     */
    public static function convertDateIntervalToSeconds(\DateInterval $interval)
    {
        $date = new \DateTime('now');
        $basicTimestamp = $date->getTimestamp();
        $shiftedTimestamp = $date->add($interval)->getTimestamp();

        return $shiftedTimestamp - $basicTimestamp;
    }

    /**
     * @param int $seconds
     * @return \DateInterval
     */
    public static function convertSecondsToDateInterval($seconds)
    {
        return new \DateInterval(sprintf('PT%dS', $seconds));
    }

    /**
     * @return array
     */
    public static function getAllowedEvents()
    {
        return array(self::EVENT_CREATE, self::EVENT_UPDATE, self::EVENT_DELETE);
    }
}
