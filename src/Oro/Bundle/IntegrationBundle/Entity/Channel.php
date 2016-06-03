<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(
 *      name="oro_integration_channel",
 *      indexes={
 *          @ORM\Index(name="oro_integration_channel_name_idx",columns={"name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository")
 * @Config(
 *      routeName="oro_integration_index",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @Oro\Loggable()
 */
class Channel
{
    /** This mode allow to do any changes(including removing) with channel */
    const EDIT_MODE_ALLOW = 3;

    /** This mode allow only to activate/deactivate channel(switch enable field) */
    const EDIT_MODE_RESTRICTED = 2;

    /** This mode do not allow to edit, remove and activate/deactivate channel */
    const EDIT_MODE_DISALLOW = 1;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Oro\Versioned()
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Oro\Versioned()
     */
    protected $type;

    /**
     * @var Transport
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Transport",
     *     cascade={"all"}, orphanRemoval=true
     * )
     */
    protected $transport;

    /**
     * @var []
     * @ORM\Column(name="connectors", type="array")
     * @Oro\Versioned()
     */
    protected $connectors;

    /**
     * @var ConfigObject
     *
     * @ORM\Column(name="synchronization_settings", type="config_object", nullable=false)
     */
    protected $synchronizationSettings;

    /**
     * @var ConfigObject
     *
     * @ORM\Column(name="mapping_settings", type="config_object", nullable=false)
     */
    protected $mappingSettings;

    /**
     * @var boolean
    *
    * @ORM\Column(name="enabled", type="boolean", nullable=true)
    * @Oro\Versioned()
    */
    protected $enabled;

    /**
     * If the status is changed by a user the previous status has to be set.
     * If the status is changed from the code, it has to be set to null.
     * For example in the listener when a channel status is changed
     *
     * @var boolean
     *
     * @ORM\Column(name="previously_enabled", type="boolean", nullable=true)
     */
    protected $previouslyEnabled;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(
     *      name="default_user_owner_id",
     *      referencedColumnName="id",
     *      onDelete="SET NULL",
     *      nullable=true
     * )
     * @Oro\Versioned()
     */
    protected $defaultUserOwner;

    /**
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(
     *      name="default_business_unit_owner_id",
     *      referencedColumnName="id",
     *      onDelete="SET NULL",
     *      nullable=true
     * )
     * @Oro\Versioned()
     */
    protected $defaultBusinessUnitOwner;

    /**
     * @var Organization
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     * @Oro\Versioned()
     */
    protected $organization;

    /**
     * @var Status[]|ArrayCollection
     *
     * Cascade persisting is not used due to lots of detach/merge
     * @ORM\OneToMany(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Status",
     *     cascade={"merge"}, orphanRemoval=true, mappedBy="channel", fetch="EXTRA_LAZY"
     * )
     * @ORM\OrderBy({"date" = "DESC"})
     */
    protected $statuses;

    /**
     * @var integer
     *
     * @ORM\Column(
     *     name="edit_mode",
     *     type="integer",
     *     nullable=false,
     *     options={"default"=\Oro\Bundle\IntegrationBundle\Entity\Channel::EDIT_MODE_ALLOW})
     * )
     */
    protected $editMode;

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
     * @deprecated Deprecated since 1.7.0 in favor of getLastStatusForConnector because of performance impact.
     * @see Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::getLastStatusForConnector
     * @param string $connector
     * @param int|null $codeFilter
     *
     * @return ArrayCollection
     */
    public function getStatusesForConnector($connector, $codeFilter = null)
    {
        return $this->statuses->filter(
            function (Status $status) use ($connector, $codeFilter) {
                $connectorFilter = $status->getConnector() === $connector;
                $codeFilter      = $codeFilter === null ? true : $status->getCode() == $codeFilter;

                return $connectorFilter && $codeFilter;
            }
        );
    }

    /**
     * @param User $owner
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
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     *
     * @deprecated in favor of isEnabled since 1.4.1 will be removed in 1.6
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->isEnabled();
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
