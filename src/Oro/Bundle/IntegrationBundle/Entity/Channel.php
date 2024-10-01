<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Config\Common\ConfigObject;

/**
 * Responsibility of channel is to split on groups transport/connectors by third party application type.
 */
#[ORM\Entity(repositoryClass: ChannelRepository::class)]
#[ORM\Table(name: 'oro_integration_channel')]
#[ORM\Index(columns: ['name'], name: 'oro_integration_channel_name_idx')]
#[Config(
    routeName: 'oro_integration_index',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Channel implements OrganizationAwareInterface
{
    /** This mode allow to do any changes(including removing) with channel */
    const EDIT_MODE_ALLOW = 3;

    /** This mode allow only to activate/deactivate channel(switch enable field) */
    const EDIT_MODE_RESTRICTED = 2;

    /** This mode do not allow to edit, remove and activate/deactivate channel */
    const EDIT_MODE_DISALLOW = 1;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    protected ?string $type = null;

    #[ORM\OneToOne(inversedBy: 'channel', targetEntity: Transport::class, cascade: ['all'], orphanRemoval: true)]
    protected ?Transport $transport = null;

    /**
     * @var []
     */
    #[ORM\Column(name: 'connectors', type: Types::ARRAY)]
    protected $connectors = [];

    /**
     * @var ConfigObject
     */
    #[ORM\Column(name: 'synchronization_settings', type: 'config_object', nullable: false)]
    protected $synchronizationSettings;

    /**
     * @var ConfigObject
     */
    #[ORM\Column(name: 'mapping_settings', type: 'config_object', nullable: false)]
    protected $mappingSettings;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $enabled = null;

    /**
     * If the status is changed by a user the previous status has to be set.
     * If the status is changed from the code, it has to be set to null.
     * For example in the listener when a channel status is changed
     *
     * @var boolean
     */
    #[ORM\Column(name: 'previously_enabled', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $previouslyEnabled = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'default_user_owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $defaultUserOwner = null;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(
        name: 'default_business_unit_owner_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    protected ?BusinessUnit $defaultBusinessUnitOwner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * @var Collection<int, Status> *
     * Cascade persisting is not used due to lots of detach/merge
     */
    #[ORM\OneToMany(
        mappedBy: 'channel',
        targetEntity: Status::class,
        cascade: ['merge'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['date' => Criteria::DESC])]
    protected ?Collection $statuses = null;

    #[ORM\Column(
        name: 'edit_mode',
        type: Types::INTEGER,
        nullable: false,
        options: ['default' => Channel::EDIT_MODE_ALLOW]
    )]
    protected int $editMode;

    public function __construct()
    {
        $this->statuses                = new ArrayCollection();
        $this->synchronizationSettings = ConfigObject::create([]);
        $this->mappingSettings         = ConfigObject::create([]);
        $this->enabled                 = true;
        $this->editMode                = self::EDIT_MODE_ALLOW;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Transport $transport
     *
     * @return $this
     */
    public function setTransport(Transport $transport)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * @return Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return $this
     */
    public function clearTransport()
    {
        $this->transport = null;

        return $this;
    }

    /**
     * @param array $connectors
     *
     * @return $this
     */
    public function setConnectors($connectors)
    {
        $this->connectors = $connectors;

        return $this;
    }

    /**
     * @return array
     */
    public function getConnectors()
    {
        return $this->connectors;
    }

    /**
     * @param ConfigObject $synchronizationSettings
     */
    public function setSynchronizationSettings($synchronizationSettings)
    {
        $this->synchronizationSettings = $synchronizationSettings;
    }

    /**
     * @return ConfigObject
     */
    public function getSynchronizationSettings()
    {
        return clone $this->synchronizationSettings;
    }

    /**
     * NOTE: object type column are immutable when changes provided in object by reference
     *
     * @return ConfigObject
     */
    public function getSynchronizationSettingsReference()
    {
        return $this->synchronizationSettings;
    }

    /**
     * @param ConfigObject $mappingSettings
     */
    public function setMappingSettings($mappingSettings)
    {
        $this->mappingSettings = $mappingSettings;
    }

    /**
     * @return ConfigObject
     */
    public function getMappingSettings()
    {
        return clone $this->mappingSettings;
    }

    /**
     * NOTE: object type column are immutable when changes provided in object by reference
     *
     * @return ConfigObject
     */
    public function getMappingSettingsReference()
    {
        return $this->mappingSettings;
    }

    /**
     * @param Status $status
     *
     * @return $this
     */
    public function addStatus(Status $status)
    {
        if (!$this->statuses->contains($status)) {
            $status->setChannel($this);
            $this->statuses->add($status);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Status[]
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @param User|null $owner
     *
     * @return $this
     */
    public function setDefaultUserOwner(User $owner = null)
    {
        $this->defaultUserOwner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getDefaultUserOwner()
    {
        return $this->defaultUserOwner;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    #[\Override]
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    #[\Override]
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param boolean $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param int $editMode
     */
    public function setEditMode($editMode)
    {
        $this->editMode = $editMode;
    }

    /**
     * @return int
     */
    public function getEditMode()
    {
        return $this->editMode;
    }

    /**
     * @return BusinessUnit
     */
    public function getDefaultBusinessUnitOwner()
    {
        return $this->defaultBusinessUnitOwner;
    }

    /**
     * @param BusinessUnit $defaultBusinessUnitOwner
     *
     * @return $this
     */
    public function setDefaultBusinessUnitOwner($defaultBusinessUnitOwner)
    {
        $this->defaultBusinessUnitOwner = $defaultBusinessUnitOwner;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @return boolean|null
     */
    public function getPreviouslyEnabled()
    {
        return $this->previouslyEnabled;
    }

    /**
     * @param boolean|null $previouslyEnabled
     */
    public function setPreviouslyEnabled($previouslyEnabled)
    {
        $this->previouslyEnabled = $previouslyEnabled;
    }
}
