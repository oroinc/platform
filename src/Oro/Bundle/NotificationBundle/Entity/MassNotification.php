<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Mass Notification entity.
 */
#[ORM\Entity]
#[ORM\Table('oro_notification_mass_notif')]
#[Config(
    routeName: 'oro_notification_massnotification_index',
    defaultValues: ['security' => ['type' => 'ACL', 'permissions' => 'VIEW', 'group_name' => '']]
)]
class MassNotification
{
    public const STATUS_FAILED  = 0;
    public const STATUS_SUCCESS = 1;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    protected ?string $email = null;

    #[ORM\Column(name: 'sender', type: Types::STRING, length: 255)]
    protected ?string $sender = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'body', type: Types::TEXT, nullable: true)]
    protected ?string $body = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $processedAt = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'status', type: Types::INTEGER)]
    protected $status;

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
     * @return MassNotification
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return MassNotification
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
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
     * @return MassNotification
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
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
     * @return MassNotification
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
