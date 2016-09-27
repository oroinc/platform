<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_user_login_history")
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository")
 *
 * @ORM\HasLifecycleCallbacks()
 */
class LoginHistory
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var UserInterface $user
     *
     * @ORM\ManyToOne(targetEntity="Symfony\Component\Security\Core\User\UserInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_class", type="string", length=255, unique=true)
     */
    protected $providerClass;

    /**
     * @var int
     *
     * @ORM\Column(name="failed_attempts", type="integer", options={"default"=0})
     */
    protected $failedAttempts;

    /**
     * @var int
     *
     * @ORM\Column(name="failed_daily_attempts", type="integer", options={"default"=0})
     */
    protected $failedDailyAttempts;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->failedAttempts = 0;
        $this->failedDailyAttempts = 0;
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getProviderClass()
    {
        return $this->providerClass;
    }

    /**
     * @param string $providerClass
     */
    public function setProviderClass($providerClass)
    {
        $this->providerClass = $providerClass;
    }

    /**
     * @return int
     */
    public function getFailedAttempts()
    {
        return $this->failedAttempts;
    }

    /**
     * @param int $failedAttempts
     */
    public function setFailedAttempts($failedAttempts)
    {
        $this->failedAttempts = $failedAttempts;
    }

    /**
     * @return int
     */
    public function getFailedDailyAttempts()
    {
        return $this->failedDailyAttempts;
    }

    /**
     * @param int $failedDailyAttempts
     */
    public function setFailedDailyAttempts($failedDailyAttempts)
    {
        $this->failedDailyAttempts = $failedDailyAttempts;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /** increase login failures by one */
    public function increaseFailedAttempts()
    {
        $this->failedAttempts++;
    }

    /** increase daily login failures by one */
    public function increaseFailedDailyAttempts()
    {
        $this->failedDailyAttempts++;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
