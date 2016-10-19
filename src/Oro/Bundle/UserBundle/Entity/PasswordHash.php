<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface as BaseUser;

/**
 * Marketing list removed items.
 *
 * @ORM\Table(name="oro_user_password_hash")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PasswordHash
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var BaseUser
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="statuses")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * The used for hashing
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $salt;

    /**
     * Encrypted password.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $hash;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return BaseUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param BaseUser $user
     */
    public function setUser(BaseUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
