<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @Config()
 * @ORM\Entity()
 * @ORM\Table(name="oro_user_impersonation")
 * @ORM\Table(name="oro_user_impersonation", indexes = {
 *      @ORM\Index("token_idx", columns = {"token"})
 * })
 */
class Impersonation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $token;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected $notify;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expire_at", type="datetime")
     */
    protected $expireAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_at", type="datetime", nullable=true)
     */
    protected $loginAt;

    /**
     * @var string $ipAddress
     *
     * @ORM\Column(name="ip_address", type="string", length=255, nullable=false, options={"default": "127.0.0.1"})
     */
    protected $ipAddress;

    public function __construct()
    {
        $this->token = bin2hex(hash('sha1', uniqid(mt_rand(), true), true));
        $this->expireAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->notify = true;
        $this->ipAddress = '127.0.0.1';
    }

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
     * @param  User $user
     * @return $this
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param \DateTime $expireAt
     * @return $this
     */
    public function setExpireAt(\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * @param \DateTime $loginAt
     * @return $this
     */
    public function setLoginAt(\DateTime $loginAt)
    {
        $this->loginAt = $loginAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoginAt()
    {
        return $this->loginAt;
    }

    /**
     * @param bool $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    /**
     * @return bool
     */
    public function hasNotify()
    {
        return $this->notify;
    }

    /**
     * @param string $ipAddress The IP address of the impersonator
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return string The IP address of the impersonator
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
}
