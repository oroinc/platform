<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
* Entity that represents Abstract Grid View
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_grid_view')]
#[ORM\Index(columns: ['discr_type'], name: 'idx_oro_grid_view_discr_type')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr_type', type: 'string')]
#[ORM\DiscriminatorMap(['grid_view' => GridView::class])]
abstract class AbstractGridView implements ViewInterface
{
    const TYPE_PRIVATE = 'private';
    const TYPE_PUBLIC  = 'public';

    /** @var array */
    protected static $types = [
        self::TYPE_PRIVATE => self::TYPE_PRIVATE,
        self::TYPE_PUBLIC  => self::TYPE_PUBLIC,
    ];

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    protected ?string $name = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [GridView::class, 'getTypes'])]
    protected ?string $type = self::TYPE_PRIVATE;

    /**
     * @var array
     */
    #[ORM\Column(type: Types::ARRAY)]
    protected $filtersData = [];

    /**
     * @var array of ['column name' => -1|1, ... ].
     * Contains information about sorters ('-1' for 'ASC', '1' for 'DESC').
     */
    #[ORM\Column(type: Types::ARRAY)]
    protected $sortersData = [];

    /**
     * @var array of ['column name' => ['renderable' => true|false, 'order' = int(0)], ... ].
     * Contains information about columns orders in the grid.
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    protected $columnsData = [];

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    protected ?string $gridName = null;

    #[ORM\ManyToOne(targetEntity: AppearanceType::class)]
    #[ORM\JoinColumn(name: 'appearanceType', referencedColumnName: 'name')]
    protected ?AppearanceType $appearanceType = null;

    /**
     * @var array
     * Contains data related to appearance, e.g. board id for boards
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    protected $appearanceData = [];

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * Collection of users who have chosen this grid view as default.
     *
     * @var ArrayCollection|AbstractGridViewUser[]
     */
    protected ?Collection $users = null;

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

    #[\Override]
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

    #[\Override]
    public function getFiltersData()
    {
        return $this->filtersData;
    }

    #[\Override]
    public function getSortersData()
    {
        return $this->sortersData;
    }

    #[\Override]
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @return AbstractUser
     */
    abstract public function getOwner();

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

    #[\Override]
    public function setFiltersData(array $filtersData = [])
    {
        $this->filtersData = $filtersData;

        return $this;
    }

    #[\Override]
    public function setSortersData(array $sortersData = [])
    {
        $this->sortersData = $sortersData;

        return $this;
    }

    #[\Override]
    public function getColumnsData()
    {
        if ($this->columnsData === null) {
            $this->columnsData = [];
        }

        return $this->columnsData;
    }

    #[\Override]
    public function setColumnsData(array $columnsData = [])
    {
        $this->columnsData = $columnsData;
    }

    #[\Override]
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }

    /**
     * @param AbstractUser|null $owner
     *
     * @return $this
     */
    abstract public function setOwner(?AbstractUser $owner = null);

    /**
     * @return View
     */
    public function createView()
    {
        $view = new View(
            $this->id,
            $this->filtersData,
            $this->sortersData,
            $this->type,
            $this->getColumnsData(),
            (string) $this->appearanceType
        );
        $view->setAppearanceData($this->getAppearanceData());
        $view->setLabel($this->name);

        return $view;
    }

    /**
     * @return AppearanceType
     */
    public function getAppearanceType()
    {
        return $this->appearanceType;
    }

    /**
     * @param AppearanceType $appearanceType
     * @return $this
     */
    public function setAppearanceType($appearanceType)
    {
        $this->appearanceType = $appearanceType;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getAppearanceTypeName()
    {
        return (string) $this->appearanceType;
    }

    /**
     * @return array
     */
    public function getAppearanceData()
    {
        if ($this->appearanceData === null) {
            $this->appearanceData = [];
        }

        return $this->appearanceData;
    }

    /**
     * @param array $appearanceData
     * @return $this
     */
    public function setAppearanceData(array $appearanceData = [])
    {
        $this->appearanceData = $appearanceData;

        return $this;
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
     * @param OrganizationInterface|null $organization
     *
     * @return $this
     */
    public function setOrganization(?OrganizationInterface $organization = null)
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
     * @return ArrayCollection|AbstractGridViewUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param AbstractGridViewUser $user
     *
     * @return $this
     */
    public function addUser(AbstractGridViewUser $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    /**
     * @param AbstractGridViewUser $user
     *
     * @return $this
     */
    public function removeUser(AbstractGridViewUser $user)
    {
        $this->users->removeElement($user);

        return $this;
    }
}
