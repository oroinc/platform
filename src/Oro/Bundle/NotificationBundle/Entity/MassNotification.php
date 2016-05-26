<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * MassNotification
 *
 * @ORM\Table("oro_notification_mass_notif")
 * @ORM\Entity(repositoryClass="Oro\Bundle\NotificationBundle\Entity\Repository\MassNotificationRepository")
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class MassNotification
{
    const STATUS_FAILED  = 1;
    const STATUS_SUCCESS = 2;

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
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;
    
    /**
     * @var string
     *
     * @ORM\Column(name="sender", type="string", length=255)
     */
    protected $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    protected $body;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $scheduledAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $processedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer")
     */
    protected $status;

    /**
     * @var \Swift_Mime_Message
     *
     * @ORM\Column(name="message", type="object")
     */
    protected $message;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return MassNotification
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    /**
     * @param \DateTime $scheduledAt
     */
    public function setScheduledAt($scheduledAt)
    {
        $this->scheduledAt = $scheduledAt;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedAt()
    {
        return $this->processedAt;
    }

    /**
     * @param \DateTime $processedAt
     */
    public function setProcessedAt($processedAt)
    {
        $this->processedAt = $processedAt;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return \Swift_Mime_Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \Swift_Mime_Message $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
