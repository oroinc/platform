<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\MappedSuperclass
 */
class AbstractSidebarState
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
     * @ORM\Column(name="position", type="string", length=13, nullable=false)
     */
    protected $position;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", nullable=false, length=17)
     */
    protected $state;

    /**
     * @var AbstractUser
     */
    protected $user;

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
     * Set position
     *
     * @param string $position
     * @return Widget
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return AbstractUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param AbstractUser $user
     * @return Widget
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Widget
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }
}
