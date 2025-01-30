<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository;

/**
 * User Email Origin
 */
#[ORM\Entity(repositoryClass: UserEmailOriginRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserEmailOrigin extends EmailOrigin
{
    #[ORM\Column(name: 'imap_host', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $imapHost = null;

    #[ORM\Column(name: 'imap_port', type: Types::INTEGER, length: 10, nullable: true)]
    protected ?int $imapPort = null;

    #[ORM\Column(name: 'smtp_host', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $smtpHost = null;

    #[ORM\Column(name: 'smtp_port', type: Types::INTEGER, length: 10, nullable: true)]
    protected ?int $smtpPort = null;

    /**
     * The SSL type to be used to connect to IMAP server. Can be empty string, 'ssl' or 'tls'
     */
    #[ORM\Column(name: 'imap_ssl', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $imapEncryption = null;

    /**
     * The SSL type to be used to connect to SMTP server. Can be empty string, 'ssl' or 'tls'
     */
    #[ORM\Column(name: 'smtp_encryption', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $smtpEncryption = null;

    #[ORM\Column(name: 'imap_user', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $user = null;

    /**
     * Encrypted password. Must be persisted.
     */
    #[ORM\Column(name: 'imap_password', type: Types::TEXT, length: 16777216, nullable: true)]
    protected ?string $password = null;

    #[ORM\OneToOne(mappedBy: 'origin', targetEntity: Mailbox::class)]
    protected ?Mailbox $mailbox = null;

    #[ORM\Column(name: 'access_token', type: Types::TEXT, length: 8192, nullable: true)]
    protected ?string $accessToken = null;

    #[ORM\Column(name: 'refresh_token', type: Types::TEXT, length: 8192, nullable: true)]
    protected ?string $refreshToken = null;

    #[ORM\Column(name: 'access_token_expires_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $accessTokenExpiresAt = null;

    #[ORM\Column(
        name: 'account_type',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['default' => 'other']
    )]
    protected ?string $accountType = 'other';

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $tenant;

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
        $this->smtpPort = (int)$smtpPort;

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
        $this->imapPort = (int)$imapPort;

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
     * Returns type.
     */
    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    /**
     * Sets type
     */
    public function setAccountType(string $accountType): UserEmailOrigin
    {
        $this->accountType = $accountType;
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
        $token = $this->getAccessToken();

        if (!empty($smtpHost) && $smtpPort > 0 && !empty($user) && (!empty($password) || !empty($token))) {
            return true;
        }

        return false;
    }

    /**
     * Check is configured imap.
     *
     * @return bool
     */
    public function isImapConfigured()
    {
        $imapHost = $this->getImapHost();
        $imapPort = $this->getImapPort();
        $user = $this->getUser();
        $password = $this->getPassword();
        $token = $this->getAccessToken();

        if (!empty($imapHost) && $imapPort > 0 && !empty($user) && (!empty($password) || !empty($token))) {
            return true;
        }

        return false;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    #[\Override]
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

    #[ORM\PrePersist]
    public function beforeSave()
    {
        if ($this->mailboxName === null) {
            $this->mailboxName = $this->user;
        }
    }

    /**
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     *
     * @return UserEmailOrigin
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     *
     * @return UserEmailOrigin
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAccessTokenExpiresAt()
    {
        return $this->accessTokenExpiresAt;
    }

    /**
     * @param \DateTime|null $datetime
     *
     * @return UserEmailOrigin
     */
    public function setAccessTokenExpiresAt(?\DateTime $datetime = null)
    {
        $this->accessTokenExpiresAt = $datetime;

        return $this;
    }

    /**
     * @param string $value
     */
    public function setClientId($value)
    {
        $this->clientId = $value;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Sets tenant
     *
     * @param string $tenant
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Returns tenant
     *
     * @return string
     */
    public function getTenant()
    {
        return $this->tenant;
    }
}
