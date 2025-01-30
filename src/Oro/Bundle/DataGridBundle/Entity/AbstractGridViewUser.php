<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
* Entity that represents Abstract Grid View User
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_grid_view_user_rel')]
#[ORM\Index(columns: ['type'], name: 'idx_oro_grid_view_user_rel_type')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['grid_view_user' => GridViewUser::class])]
abstract class AbstractGridViewUser
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'alias', type: Types::STRING)]
    protected ?string $alias = null;

    #[ORM\Column(name: 'grid_name', type: Types::STRING)]
    protected ?string $gridName = null;

    protected ?AbstractGridView $gridView = null;

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
    abstract public function setUser(?AbstractUser $user = null);

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
