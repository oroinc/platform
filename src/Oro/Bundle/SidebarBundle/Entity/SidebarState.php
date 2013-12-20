<?php

namespace Oro\Bundle\SidebarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Sidebar state storage
 *
 * @ORM\Table(
 *    name="oro_sidebar_state",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="sidebar_state_unique_idx", columns={"user_id", "position"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SidebarBundle\Entity\Repository\SidebarStateRepository")
 */
class SidebarState
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
     * @var \Oro\Bundle\UserBundle\Entity\User $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Exclude
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", nullable=false, length=17)
     */
    protected $state;

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
     * @return \Oro\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \Oro\Bundle\UserBundle\Entity\User $user
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
