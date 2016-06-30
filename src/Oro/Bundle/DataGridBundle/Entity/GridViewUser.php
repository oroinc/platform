<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository")
 * @ORM\Table(name="oro_grid_view_user")
 */
class GridViewUser
{
    /**
     * @var int $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string")
     */
    protected $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="grid_name", type="string")
     */
    protected $gridName;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $user;


    /**
     * @var GridView
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\DataGridBundle\Entity\GridView", inversedBy="users")
     * @ORM\JoinColumn(name="grid_view_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $gridView;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return GridView
     */
    public function getGridView()
    {
        return $this->gridView;
    }

    /**
     * @return string
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param GridView $gridView
     *
     * @return $this
     */
    public function setGridView(GridView $gridView)
    {
        $this->gridView = $gridView;

        return $this;
    }

    /**
     * @param string $gridName
     *
     * @return $this
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }
}
