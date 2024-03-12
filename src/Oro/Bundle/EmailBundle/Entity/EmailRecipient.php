<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;

/**
 * Email Recipient
 */
#[ORM\Entity(repositoryClass: EmailRecipientRepository::class)]
#[ORM\Table(name: 'oro_email_recipient')]
#[ORM\Index(columns: ['email_id', 'type'], name: 'email_id_type_idx')]
class EmailRecipient
{
    const TO = 'to';
    const CC = 'cc';
    const BCC = 'bcc';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 320)]
    protected ?string $name = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 3)]
    protected ?string $type = null;

    #[ORM\ManyToOne(targetEntity: EmailAddress::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'email_address_id', referencedColumnName: 'id', nullable: false)]
    protected ?EmailAddress $emailAddress = null;

    #[ORM\ManyToOne(targetEntity: Email::class, inversedBy: 'recipients')]
    #[ORM\JoinColumn(name: 'email_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Email $email = null;

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
