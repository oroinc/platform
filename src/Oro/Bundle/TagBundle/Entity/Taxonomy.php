<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroTagBundle_Entity_Taxonomy;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TagBundle\Form\Type\TaxonomySelectType;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Tag Taxonomy Entity
 *
 * @mixin OroTagBundle_Entity_Taxonomy
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_tag_taxonomy')]
#[ORM\Index(columns: ['name', 'organization_id'], name: 'tag_taxonomy_name_organization_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_taxonomy_index',
    routeView: 'oro_taxonomy_view',
    defaultValues: [
        'entity' => ['icon' => 'icon-tag'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'grouping' => ['groups' => ['dictionary']],
        'dictionary' => ['virtual_fields' => ['id'], 'search_fields' => ['name'], 'representation_field' => 'name'],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true],
        'tag' => ['immutable' => true],
        'form' => ['form_type' => TaxonomySelectType::class, 'grid_name' => 'taxonomy-select-grid']
    ]
)]
class Taxonomy implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    protected ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updated = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\OneToMany(mappedBy: 'taxonomy', targetEntity: Tag::class, fetch: 'LAZY')]
    protected ?Collection $tags = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'background_color', type: Types::STRING, length: 7, nullable: true)]
    protected ?string $backgroundColor = null;

    /**
     * Constructor
     *
     * @param string $name Tag's name
     */
    public function __construct($name = null)
    {
        $this->tags = new ArrayCollection();
        $this->setName($name);
    }

    /**
     * Returns taxonomy's id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the taxonomy's name
     *
     * @param string $name Name to set
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns taxonomy's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created date
     *
     * @param \DateTime $date
     * @return $this
     */
    public function setCreated(\DateTime $date)
    {
        $this->created = $date;
        return $this;
    }

    /**
     * Get created date
     *
     * @return \Datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated date
     *
     * @param \DateTime $date
     * @return $this
     */
    public function setUpdated(\DateTime $date)
    {
        $this->updated = $date;
        return $this;
    }

    /**
     * Get updated date
     *
     * @return \Datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * To string
     *
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function doUpdate()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owningUser
     * @return Taxonomy
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;
        return $this;
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     * @return Taxonomy
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Gets a background color of this events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, e.g. #FF0000.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of this events.
     *
     * @param string|null $backgroundColor The color in hex format, e.g. #FF0000.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return Taxonomy
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }
}
