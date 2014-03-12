<?php

namespace Oro\Bundle\ReminderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;

/**
 * Reminder
 *
 * @ORM\Table(name="oro_reminder", indexes={
 *     @ORM\Index(name="reminder_state_idx", columns={"state"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository")
 * @Oro\Loggable
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-bell-o"
 *      },
 *      "dataaudit"={"auditable"=true}
 *  }
 * )
 */
class Reminder
{
    const STATE_SENT = 'sent';
    const STATE_NOT_SENT = 'not_sent';
    const STATE_FAIL = 'fail';
    const STATE_REQUESTED = 'requested';

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
     * @ORM\Column(name="subject", type="string", length=32, nullable=false)
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
     * @ORM\Column(name="start_at", type="datetime", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $startAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expire_at", type="datetime", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $expireAt;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=255, nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $method;

    /**
     * @var ReminderInterval
     */
    protected $interval;

    /**
     * @var integer $intervalNumber
     *
     * @ORM\Column(name="interval_number", type="integer", nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $intervalNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="interval_unit", type="string", length=1, nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $intervalUnit;

    /**
     * @var string $state
     *
     * @ORM\Column(name="state", type="string", length=32, nullable=false)
     * @Oro\Versioned
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true}
     *  }
     * )
     */
    protected $state;

    /**
     * @var integer
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
     * @var integer
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
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="recipient_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $recipient;

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
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    protected $sentAt;

    /**
     * @var array
     *
     * @ORM\Column(name="failure_exception", type="array", nullable=true)
     */
    protected $failureException;

    public function __construct()
    {
        $this->setState(self::STATE_NOT_SENT);
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
     * Get start DateTime
     *
     * @return \DateTime
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * Set expiration DateTime
     *
     * @param \DateTime $expireAt
     * @return Reminder
     */
    public function setExpireAt(\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;

        $this->syncStartAtAndInterval();

        return $this;
    }

    /**
     * Get expiration DateTime
     *
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * Set method
     *
     * @param string $method
     * @return Reminder
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set remind interval
     *
     * @param ReminderInterval $interval
     * @return Reminder
     */
    public function setInterval(ReminderInterval $interval)
    {
        $this->interval = $interval;

        $this->syncStartAtAndInterval();

        return $this;
    }

    /**
     * Update start date
     */
    protected function syncStartAtAndInterval()
    {
        if ($this->expireAt) {
            $this->startAt = clone $this->expireAt;
            $this->startAt->sub($this->getInterval()->createDateInterval());
        }
        if ($this->interval) {
            $this->intervalNumber = $this->interval->getNumber();
            $this->intervalUnit = $this->interval->getUnit();
        }
    }

    /**
     * Get remind interval
     *
     * @return ReminderInterval
     */
    public function getInterval()
    {
        if (!$this->interval) {
            $this->interval = new ReminderInterval($this->intervalNumber, $this->intervalUnit);
        }
        return $this->interval;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Reminder
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set related entity id
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
     * Get related entity id
     *
     * @return integer
     */
    public function getRelatedEntityId()
    {
        return $this->relatedEntityId;
    }

    /**
     * Set related entity class name
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
     * Get related entity class name
     *
     * @return string
     */
    public function getRelatedEntityClassName()
    {
        return $this->relatedEntityClassName;
    }

    /**
     * Set recipient
     *
     * @param User $owner
     * @return Reminder
     */
    public function setRecipient(User $owner)
    {
        $this->recipient = $owner;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return User
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Sets reminder data
     *
     * @param ReminderDataInterface $data
     */
    public function setReminderData(ReminderDataInterface $data)
    {
        $this->setSubject($data->getSubject());
        $this->setExpireAt($data->getExpireAt());
        $this->setRecipient($data->getRecipient());
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
     * Get failure exceptions
     *
     * @return array
     */
    public function getFailureException()
    {
        return $this->failureException;
    }

    /**
     * Add a failure exception
     *
     * @param \Exception $e
     *
     * @return Reminder
     */
    public function setFailureException(\Exception $e)
    {
        $this->failureException = [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString()
        ];

        return $this;
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
     * @return string
     */
    public function __toString()
    {
        return (string)$this->subject;
    }
}
