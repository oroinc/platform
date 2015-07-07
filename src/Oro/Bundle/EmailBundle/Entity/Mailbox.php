<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
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
class Mailbox implements EmailHolderInterface
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
     * @var bool
     *
     * @ORM\Column(name="imap_enabled", type="boolean")
     */
    protected $imapEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_host", type="string", length=255)
     */
    protected $imapHost;

    /**
     * @var integer
     *
     * @ORM\Column(name="imap_port", type="integer")
     */
    protected $imapPort;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_encryption", type="string", length=50)
     */
    protected $imapEncryption;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_username", type="string", length=255)
     */
    protected $imapUsername;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_password", type="string", length=255)
     */
    protected $imapPassword;

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
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
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
     * @param MailboxProcessor $processor
     *
     * @return $this
     */
    public function setProcessor(MailboxProcessor $processor)
    {
        $this->processor = $processor;

        return $this;
    }

    /**
     * @return $this
     */
    public function clearProcessor()
    {
        $this->processor = null;

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

    /**
     * @return string
     */
    public function getImapEncryption()
    {
        return $this->imapEncryption;
    }

    /**
     * @param string $imapEncryption
     *
     * @return $this
     */
    public function setImapEncryption($imapEncryption)
    {
        $this->imapEncryption = $imapEncryption;

        return $this;
    }

    /**
     * @return string
     */
    public function getImapHost()
    {
        return $this->imapHost;
    }

    /**
     * @param string $imapHost
     *
     * @return $this
     */
    public function setImapHost($imapHost)
    {
        $this->imapHost = $imapHost;

        return $this;
    }

    /**
     * @return string
     */
    public function getImapPassword()
    {
        return $this->imapPassword;
    }

    /**
     * @param string $imapPassword
     *
     * @return $this
     */
    public function setImapPassword($imapPassword)
    {
        $this->imapPassword = $imapPassword;

        return $this;
    }

    /**
     * @return int
     */
    public function getImapPort()
    {
        return $this->imapPort;
    }

    /**
     * @param int $imapPort
     *
     * @return $this
     */
    public function setImapPort($imapPort)
    {
        $this->imapPort = $imapPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getImapUsername()
    {
        return $this->imapUsername;
    }

    /**
     * @param string $imapUsername
     *
     * @return $this
     */
    public function setImapUsername($imapUsername)
    {
        $this->imapUsername = $imapUsername;

        return $this;
    }
}
