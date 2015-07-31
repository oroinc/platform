<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * User Email Origin
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class UserEmailOrigin extends EmailOrigin
{
    /**
     * @var string
     *
     * @ORM\Column(name="imap_host", type="string", length=255, nullable=true)
     */
    protected $imapHost;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_port", type="integer", length=10, nullable=true)
     */
    protected $imapPort;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_host", type="string", length=255, nullable=true)
     */
    protected $smtpHost;

    /**
     * @var string
     *
     * @ORM\Column(name="smtp_port", type="integer", length=10, nullable=true)
     */
    protected $smtpPort;

    /**
     * The SSL type to be used to connect to IMAP server. Can be empty string, 'ssl' or 'tls'
     *
     * @var string
     *
     * @ORM\Column(name="imap_ssl", type="string", length=3, nullable=true)
     */
    protected $imapEncryption;

    /**
     * The SSL type to be used to connect to SMTP server. Can be empty string, 'ssl' or 'tls'
     *
     * @var string
     *
     * @ORM\Column(name="smtp_encryption", type="string", length=3, nullable=true)
     */
    protected $smtpEncryption;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_user", type="string", length=100, nullable=true)
     */
    protected $user;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     *
     * @ORM\Column(name="imap_password", type="string", length=100, nullable=true)
     */
    protected $password;

    /**
     * Gets the host name of IMAP server
     *
     * @return string
     */
    public function getImapHost()
    {
        return $this->imapHost;
    }

    /**
     * Sets the host name of IMAP server
     *
     * @param string $imapHost
     *
     * @return UserEmailOrigin
     */
    public function setImapHost($imapHost)
    {
        $this->imapHost = $imapHost;

        return $this;
    }

    /**
     * Gets the host name of SMTP server
     *
     * @return string
     */
    public function getSmtpHost()
    {
        return $this->smtpHost;
    }

    /**
     * Sets the host name of SMTP server
     *
     * @param string $smtpHost
     *
     * @return UserEmailOrigin
     */
    public function setSmtpHost($smtpHost)
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    /**
     * Gets the port of SMTP server
     *
     * @return int
     */
    public function getSmtpPort()
    {
        return $this->smtpPort;
    }

    /**
     * Sets the port of SMTP server
     *
     * @param int $smtpPort
     *
     * @return UserEmailOrigin
     */
    public function setSmtpPort($smtpPort)
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    /**
     * Gets the port of IMAP server
     *
     * @return int
     */
    public function getImapPort()
    {
        return $this->imapPort;
    }

    /**
     * Sets the port of IMAP server
     *
     * @param int $imapPort
     *
     * @return UserEmailOrigin
     */
    public function setImapPort($imapPort)
    {
        $this->imapPort = $imapPort;

        return $this;
    }

    /**
     * Gets the SSL type to be used to connect to IMAP server
     *
     * @return string
     */
    public function getImapEncryption()
    {
        return $this->imapEncryption;
    }

    /**
     * Sets the SSL type to be used to connect to IMAP server
     *
     * @param string $imapEncryption Can be empty string, 'ssl' or 'tls'
     * @return UserEmailOrigin
     */
    public function setImapEncryption($imapEncryption)
    {
        $this->imapEncryption = $imapEncryption;

        return $this;
    }

    /**
     * Gets the SSL type to be used to connect to SMTP server
     *
     * @return string
     */
    public function getSmtpEncryption()
    {
        return $this->smtpEncryption;
    }

    /**
     * Sets the SSL type to be used to connect to SMTP server
     *
     * @param string $smtpEncryption Can be empty string, 'ssl' or 'tls'
     * @return UserEmailOrigin
     */
    public function setSmtpEncryption($smtpEncryption)
    {
        $this->smtpEncryption = $smtpEncryption;

        return $this;
    }

    /**
     * Gets the user name
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user name
     *
     * @param string $user
     * @return UserEmailOrigin
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets the encrypted password. Before use the password must be decrypted.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the password. The password must be encrypted.
     *
     * @param  string $password New encrypted password
     * @return UserEmailOrigin
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Check is configured smtp.
     *
     * @return bool
     */
    public function isSmtpConfigured()
    {
        $smtpHost = $this->getSmtpHost();
        $smtpPort = $this->getSmtpPort();
        $user = $this->getUser();
        $password = $this->getPassword();
        $encryption = $this->getSmtpEncryption();

        if (!empty($smtpHost) && $smtpPort > 0 && !empty($user) && !empty($password) && !empty($encryption)) {
            return true;
        }

        return false;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        $host = '';
        if ($this->imapHost) {
            $host = $this->imapHost;
        } elseif ($this->smtpHost) {
            $host = $this->smtpHost;
        }

        return sprintf('%s (%s)', $this->user, $host);
    }

    /**
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        if ($this->mailboxName === null) {
            $this->mailboxName = $this->user;
        }
    }
}
