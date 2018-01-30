<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Email Recipient
 *
 * @ORM\Table(name="oro_email_recipient", indexes={
 *     @ORM\Index("email_id_type_idx", columns = {"email_id", "type"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository")
 */
class EmailRecipient
{
    const TO = 'to';
    const CC = 'cc';
    const BCC = 'bcc';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=320)
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=3)
     * @JMS\Type("string")
     */
    protected $type;

    /**
     * @var EmailAddress
     *
     * @ORM\ManyToOne(targetEntity="EmailAddress", fetch="EAGER")
     * @ORM\JoinColumn(name="email_address_id", referencedColumnName="id", nullable=false)
     * @JMS\Exclude
     */
    protected $emailAddress;

    /**
     * @var Email
     *
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="recipients")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $email;

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
     * Get full email name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set full email name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get recipient type.
     *
     * @return string Can be 'to', 'cc' or 'bcc'
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set recipient type
     *
     * @param string $type Can be 'to', 'cc' or 'bcc'
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get email address
     *
     * @return EmailAddress
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Set email address
     *
     * @param EmailAddress $emailAddress
     * @return $this
     */
    public function setEmailAddress(EmailAddress $emailAddress)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param Email $email
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}
