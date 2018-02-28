<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository")
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          }
 *      }
 * )
 * @UniqueEntity(
 *      fields={"name", "owner", "gridName", "organization"},
 *      message="oro.datagrid.gridview.unique"
 * )
 */
class GridView extends AbstractGridView
{
    /**
     * {@inheritdoc}
     *
     * @ORM\OneToMany(
     *      targetEntity="Oro\Bundle\DataGridBundle\Entity\GridViewUser",
     *      mappedBy="gridView",
     *      cascade={"ALL"},
     *      fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="oro_grid_view_user_rel",
     *     joinColumns={@ORM\JoinColumn(name="id", referencedColumnName="grid_view_id", onDelete="CASCADE")}
     * )
     */
    protected $users;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $owner;

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
