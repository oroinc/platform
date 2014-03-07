<?php

namespace Oro\Bundle\ReminderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ReminderBundle\Model\ReminderState;

/**
 * Reminder
 *
 * @ORM\Table(name="oro_reminder", indexes={
 *     @ORM\Index(name="reminder_is_sent_idx", columns={"is_sent"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository")
 * @Oro\Loggable
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-bell-o"
 *      },
 *      "ownership"={
 *          "owner_type"="USER",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="owner_id"
 *      },
 *      "security"={
 *          "type"="ACL"
 *      },
 *      "dataaudit"={"auditable"=true}
 *  }
 * )
 */
class Reminder
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $subject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due_date", type="datetime", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $dueDate;

    /**
     * @var integer $reminderInterval
     *
     * @ORM\Column(name="reminder_interval", type="integer", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $reminderInterval;

    /**
     * @var ReminderState $state
     *
     * @ORM\Column(name="state", type="object", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $state;

    /**
     * @var integer $relatedEntityId
     *
     * @ORM\Column(name="related_entity_id", type="integer", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $relatedEntityId;

    /**
     * @var integer $relatedEntityClassName
     *
     * @ORM\Column(name="related_entity_classname", type="string", length=255, nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $relatedEntityClassName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=false)
     */
    protected $sentAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_sent", type="boolean")
     */
    protected $isSent = false;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->subject;
    }

    public function __construct()
    {
        $this->state = new ReminderState();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Reminder
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set dueDate
     *
     * @param \DateTime $dueDate
     * @return Reminder
     */
    public function setDueDate(\DateTime $dueDate)
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * Get dueDate
     *
     * @return \DateTime
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Set reminderInterval
     *
     * @param integer $reminderInterval
     * @return Reminder
     */
    public function setReminderInterval($reminderInterval)
    {
        $this->reminderInterval = $reminderInterval;

        return $this;
    }

    /**
     * Get reminderInterval
     *
     * @return integer
     */
    public function getReminderInterval()
    {
        return $this->reminderInterval;
    }

    /**
     * Set state
     *
     * @param ReminderState $state
     * @return Reminder
     */
    public function setState(ReminderState $state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return ReminderState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set relatedEntityId
     *
     * @param integer $relatedEntityId
     * @return Reminder
     */
    public function setRelatedEntityId($relatedEntityId)
    {
        $this->relatedEntityId = $relatedEntityId;

        return $this;
    }

    /**
     * Get relatedEntityId
     *
     * @return integer
     */
    public function getRelatedEntityId()
    {
        return $this->relatedEntityId;
    }

    /**
     * Set relatedEntityClassName
     *
     * @param string $relatedEntityClassName
     * @return Reminder
     */
    public function setRelatedEntityClassName($relatedEntityClassName)
    {
        $this->relatedEntityClassName = $relatedEntityClassName;

        return $this;
    }

    /**
     * Get relatedEntityClassName
     *
     * @return string
     */
    public function getRelatedEntityClassName()
    {
        return $this->relatedEntityClassName;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Reminder
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Reminder
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     * @return Reminder
     */
    public function setSentAt(\DateTime $sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set isSent
     *
     * @param boolean $isSent
     * @return Reminder
     */
    public function setSent($isSent)
    {
        $this->isSent = $isSent;

        return $this;
    }

    /**
     * Get isSent
     *
     * @return boolean
     */
    public function isSent()
    {
        return $this->isSent;
    }
}
