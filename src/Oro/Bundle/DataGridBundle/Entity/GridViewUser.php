<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository")
 */
class GridViewUser extends AbstractGridViewUser
{
    /**
     * @var AbstractGridView
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DataGridBundle\Entity\GridView", inversedBy="users")
     * @ORM\JoinColumn(name="grid_view_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $gridView;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }
}
