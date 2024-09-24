<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Extend\Entity\Autocomplete\OroTagBundle_Entity_Tag;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Tag entity
 *
 * @mixin OroTagBundle_Entity_Tag
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'oro_tag_tag')]
#[ORM\Index(columns: ['name', 'organization_id'], name: 'name_organization_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_tag_index',
    defaultValues: [
        'entity' => ['icon' => 'fa-tag'],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'grouping' => ['groups' => ['dictionary']],
        'dictionary' => ['virtual_fields' => ['id'], 'search_fields' => ['name'], 'representation_field' => 'name'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'comment' => ['immutable' => true],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true],
        'tag' => ['immutable' => true]
    ]
)]
class Tag implements ExtendEntityInterface
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
     * @var Collection<int, Tagging>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: Tagging::class, fetch: 'EXTRA_LAZY')]
    protected ?Collection $tagging = null;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class, fetch: 'LAZY', inversedBy: 'tags')]
    #[ORM\JoinColumn(name: 'taxonomy_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Taxonomy $taxonomy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * Constructor
     *
     * @param string $name Tag's name
     */
    public function __construct($name = null)
    {
        $this->setName($name);
        $this->tagging = new ArrayCollection();
    }

    /**
     * Returns tag's id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag's name
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
     * Returns tag's name
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
     * Return tagging object
     *
     * @return PersistentCollection
     */
    public function getTagging()
    {
        return $this->tagging;
    }

    public function addTagging(Tagging $tagging)
    {
        if (!$this->tagging->contains($tagging)) {
            $this->tagging->add($tagging);
        }
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
     * @return Tag
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
     * @return Tag
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
     * @return Taxonomy
     */
    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    /**
     * @param Taxonomy $taxonomy
     */
    public function setTaxonomy($taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        if ($this->getTaxonomy() === null) {
            return null;
        }

        return $this->getTaxonomy()->getBackgroundColor();
    }
}
