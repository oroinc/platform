<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(
 *  "oro_process_trigger",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="process_trigger_unique_idx",
 *          columns={"event", "field", "definition_name", "cron"}
 *      )
 *  }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "note"={
 *              "immutable"=true
 *          },
 *          "comment"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
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
     * @ORM\Column(name="event", type="string", length=255, nullable=true)
     */
    protected $event;

    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=255, nullable=true)
     */
    protected $field;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="smallint")
     */
    protected $priority = Job::PRIORITY_DEFAULT;

    /**
     * Whether process should be queued or processed immediately
     *
     * @var boolean
     *
     * @ORM\Column(name="queued", type="boolean")
     */
    protected $queued = false;

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
     * @var string
     *
     * @ORM\Column(name="cron", type="string", length=100, nullable=true)
     */
    protected $cron;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
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
     * @param int $id
     * @return ProcessTrigger
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @param integer $priority
     * @return ProcessTrigger
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
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
     * @return ProcessTrigger
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;

        return $this;
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
     * @param string $cron
     * @return ProcessTrigger
     */
    public function setCron($cron)
    {
        $this->cron = $cron;

        return $this;
    }

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
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
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
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
        return [self::EVENT_CREATE, self::EVENT_UPDATE, self::EVENT_DELETE];
    }

    /**
     * @param ProcessTrigger $trigger
     * @return ProcessTrigger
     */
    public function import(ProcessTrigger $trigger)
    {
        $this->setEvent($trigger->getEvent())
            ->setField($trigger->getField())
            ->setPriority($trigger->getPriority())
            ->setQueued($trigger->isQueued())
            ->setTimeShift($trigger->getTimeShift())
            ->setDefinition($trigger->getDefinition())
            ->setCron($trigger->getCron());

        return $this;
    }

    /**
     * @param ProcessTrigger $trigger
     * @return bool
     */
    public function isDefinitiveEqual(ProcessTrigger $trigger)
    {
        if ($this->event !== $trigger->getEvent()
            || $this->field !== $trigger->getField()
            || $this->cron !== $trigger->getCron()
        ) {
            return false;
        }

        $ownDefinitionName = $this->definition ? $this->definition->getName() : null;
        $outerDefinitionName = $trigger->getDefinition() ? $trigger->getDefinition()->getName() : null;

        return $ownDefinitionName === $outerDefinitionName;
    }
}
