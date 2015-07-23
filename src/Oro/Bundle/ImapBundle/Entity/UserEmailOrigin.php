<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * IMAP Email Origin
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
    protected $host;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_port", type="integer", length=10, nullable=true)
     */
    protected $port;

    /**
     * The SSL type to be used to connect to IMAP server. Can be empty string, 'ssl' or 'tls'
     *
     * @var string
     *
     * @ORM\Column(name="imap_ssl", type="string", length=3, nullable=true)
     */
    protected $ssl;

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
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the host name of IMAP server
     *
     * @param string $host
     * @return UserEmailOrigin
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Gets the port of IMAP server
     *
     * @return int
     */
    public function getPort()
    {
        return (int)$this->port;
    }

    /**
     * Sets the port of IMAP server
     *
     * @param int $port
     * @return UserEmailOrigin
     */
    public function setPort($port)
    {
        $this->port = (int)$port;

        return $this;
    }

    /**
     * Gets the SSL type to be used to connect to IMAP server
     *
     * @return string
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Sets the SSL type to be used to connect to IMAP server
     *
     * @param string $ssl Can be empty string, 'ssl' or 'tls'
     * @return UserEmailOrigin
     */
    public function setSsl($ssl)
    {
        $this->ssl = $ssl;

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
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s (%s)', $this->user, $this->host);
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
