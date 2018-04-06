<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="oro_grid_view_user_rel",
 *     indexes={
 *         @ORM\Index(name="idx_oro_grid_view_user_rel_type", columns={"type"})
 *     }
 * )
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"grid_view_user" = "Oro\Bundle\DataGridBundle\Entity\GridViewUser"})
 */
abstract class AbstractGridViewUser
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
     * @var AbstractGridView
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
     * @return AbstractUser
     */
    abstract public function getUser();

    /**
     * @return AbstractGridView
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
     * @param AbstractUser|null $user
     *
     * @return $this
     */
    abstract public function setUser(AbstractUser $user = null);

    /**
     * @param AbstractGridView $gridView
     *
     * @return $this
     */
    public function setGridView(AbstractGridView $gridView)
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
