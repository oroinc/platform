<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
* Entity that represents Grid View
*
*/
#[ORM\Entity(repositoryClass: GridViewRepository::class)]
#[UniqueEntity(fields: ['name', 'owner', 'gridName', 'organization'], message: 'oro.datagrid.gridview.unique')]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management']
    ]
)]
class GridView extends AbstractGridView
{
    /**
     * {@inheritdoc}
     * @var Collection<int, GridViewUser>
     */
    #[ORM\JoinTable(name: 'oro_grid_view_user_rel')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'grid_view_id', onDelete: 'CASCADE')]
    #[ORM\OneToMany(mappedBy: 'gridView', targetEntity: GridViewUser::class, cascade: ['ALL'], fetch: 'EXTRA_LAZY')]
    protected ?Collection $users = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotBlank]
    protected ?User $owner = null;

    /**
     * {@inheritdoc}
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(AbstractUser $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
