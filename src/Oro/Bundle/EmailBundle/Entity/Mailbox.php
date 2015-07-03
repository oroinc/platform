<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

/**
 * @ORM\Table(name="oro_email_mailbox")
 * @ORM\Entity
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          }
 *      }
 * )
 */
class Mailbox implements EmailOwnerInterface
{

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
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @var MailboxProcessor
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\MailboxProcessor",
     *     cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="processor_id", referencedColumnName="id")
     */
    protected $processor;

    /**
     * @var EmailOrigin
     *
     * @ORM\OneToOne(targetEntity="EmailOrigin")
     * @ORM\JoinColumn(name="imap_origin_id", referencedColumnName="id")
     */
    protected $imapOrigin;

    /**
     * @var bool
     *
     * @ORM\Column(name="imap_enabled", type="boolean")
     */
    protected $imapEnabled;

    /**
     * @var bool
     *
     * @ORM\Column(name="smtp_enabled", type="boolean")
     */
    protected $smtpEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_host", type="string", length=255)
     */
    protected $smtpHost;

    /**
     * @var integer
     *
     * @ORM\Column(name="smtp_port", type="integer")
     */
    protected $smtpPort;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_encryption", type="string", length=50)
     */
    protected $smtpEncryption;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_username", type="string", length=255)
     */
    protected $smtpUsername;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_password", type="string", length=255)
     */
    protected $smtpPassword;

    public function __construct()
    {
        $this->imapOrigin = new ImapEmailOrigin();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailFields()
    {
        return ['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstName()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLastName()
    {
        return $this->label;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return MailboxProcessor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param mixed $processor
     *
     * @return $this
     */
    public function setProcessor(MailboxProcessor $processor)
    {
        $this->processor = $processor;

        return $this;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return EmailOrigin
     */
    public function getImapOrigin()
    {
        return $this->imapOrigin;
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return $this
     */
    public function setImapOrigin($origin)
    {
        $this->imapOrigin = $origin;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isImapEnabled()
    {
        return $this->imapEnabled;
    }

    /**
     * @param boolean $imapEnabled
     *
     * @return $this
     */
    public function setImapEnabled($imapEnabled)
    {
        $this->imapEnabled = $imapEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSmtpEnabled()
    {
        return $this->smtpEnabled;
    }

    /**
     * @param boolean $smtpEnabled
     *
     * @return $this
     */
    public function setSmtpEnabled($smtpEnabled)
    {
        $this->smtpEnabled = $smtpEnabled;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmtpEncryption()
    {
        return $this->smtpEncryption;
    }

    /**
     * @param mixed $smtpEncryption
     *
     * @return $this
     */
    public function setSmtpEncryption($smtpEncryption)
    {
        $this->smtpEncryption = $smtpEncryption;

        return $this;
    }

    /**
     * @return string
     */
    public function getSmtpHost()
    {
        return $this->smtpHost;
    }

    /**
     * @param string $smtpHost
     *
     * @return $this
     */
    public function setSmtpHost($smtpHost)
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmtpPassword()
    {
        return $this->smtpPassword;
    }

    /**
     * @param mixed $smtpPassword
     *
     * @return $this
     */
    public function setSmtpPassword($smtpPassword)
    {
        $this->smtpPassword = $smtpPassword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmtpPort()
    {
        return $this->smtpPort;
    }

    /**
     * @param mixed $smtpPort
     *
     * @return $this
     */
    public function setSmtpPort($smtpPort)
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmtpUsername()
    {
        return $this->smtpUsername;
    }

    /**
     * @param mixed $smtpUsername
     *
     * @return $this
     */
    public function setSmtpUsername($smtpUsername)
    {
        $this->smtpUsername = $smtpUsername;

        return $this;
    }
}
