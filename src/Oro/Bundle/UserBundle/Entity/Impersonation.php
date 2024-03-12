<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Store user impersonations
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_user_impersonation')]
#[ORM\Index(columns: ['token'], name: 'token_idx')]
#[ORM\Index(columns: ['ip_address'], name: 'oro_user_imp_ip')]
#[Config]
class Impersonation
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $token = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    protected ?bool $notify = null;

    #[ORM\Column(name: 'expire_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $expireAt = null;

    #[ORM\Column(name: 'login_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $loginAt = null;

    #[ORM\Column(
        name: 'ip_address',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['default' => '127.0.0.1']
    )]
    protected string $ipAddress;

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
     * @param User|null $user
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
