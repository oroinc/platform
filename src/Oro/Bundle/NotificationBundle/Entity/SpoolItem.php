<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpoolItem
 *
 * @ORM\Table(name="oro_notification_email_spool",
 *      indexes={@ORM\Index(name="notification_spool_status_idx", columns={"status"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\NotificationBundle\Entity\Repository\SpoolItemRepository")
 */
class SpoolItem
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
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var \Swift_Mime_Message
     *
     * @ORM\Column(name="message", type="object")
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="log_entity_name", type="string", length=255)
     */
    private $logEntityName;

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
     * Set status
     *
     * @param string $status
     * @return SpoolItem
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set message
     *
     * @param \Swift_Mime_Message $message
     * @return SpoolItem
     */
    public function setMessage(\Swift_Mime_Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return \Swift_Mime_Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getLogEntityName()
    {
        return $this->logEntityName;
    }

    /**
     * @param  string $logEntityName
     * @return SpoolItem
     */
    public function setLogEntityName($logEntityName)
    {
        $this->logEntityName = $logEntityName;

        return $this;
    }
}
