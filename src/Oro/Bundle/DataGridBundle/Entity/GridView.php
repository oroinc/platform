<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository")
 * @ORM\Table(name="oro_grid_view")
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
 *              "group_name"=""
 *          }
 *      }
 * )
 * @UniqueEntity(
 *      fields={"name", "owner", "gridName", "organization"},
 *      message="oro.datagrid.gridview.unique"
 * )
 */
class GridView implements ViewInterface
{
    const TYPE_PRIVATE = 'private';
    const TYPE_PUBLIC  = 'public';

    /** @var array */
    protected static $types = [
        self::TYPE_PRIVATE => self::TYPE_PRIVATE,
        self::TYPE_PUBLIC  => self::TYPE_PUBLIC,
    ];

    /**
     * @var int $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Choice(callback={"Oro\Bundle\DataGridBundle\Entity\GridView", "getTypes"})
     */
    protected $type = self::TYPE_PRIVATE;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $filtersData = [];

    /**
     * @var array of ['column name' => -1|1, ... ].
     * Contains information about sorters ('-1' for 'ASC', '1' for 'DESC').
     *
     * @ORM\Column(type="array")
     */
    protected $sortersData = [];

    /**
     * @var array of ['column name' => ['renderable' => true|false, 'order' = int(0)], ... ].
     * Contains information about columns orders in the grid.
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $columnsData = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $gridName;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Collection of users who have chosen this grid view as default.
     *
     * @var ArrayCollection|User[]
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
     * GridView constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiltersData()
    {
        return $this->filtersData;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortersData()
    {
        return $this->sortersData;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
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
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFiltersData(array $filtersData = [])
    {
        $this->filtersData = $filtersData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortersData(array $sortersData = [])
    {
        $this->sortersData = $sortersData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnsData()
    {
        if ($this->columnsData === null) {
            $this->columnsData = [];
        }

        return $this->columnsData;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnsData(array $columnsData = [])
    {
        $this->columnsData = $columnsData;
    }

    /**
     * {@inheritdoc}
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return View
     */
    public function createView()
    {
        $view = new View($this->id, $this->filtersData, $this->sortersData, $this->type, $this->getColumnsData());
        $view->setLabel($this->name);

        return $view;
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return static::$types;
    }

    /**
     * Set organization
     *
     * @param OrganizationInterface $organization
     *
     * @return User
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return ArrayCollection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    /**
     * @param User $user
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }
}
