<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEntityConfigBundle_Attribute_Entity_AttributeFamily;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Layout\ContextItemInterface;

/**
 * An attribute family is a set of the attributes that are enough to store complete information
 * about the entities of a similar type
 *
 * @method File getImage()
 * @method AttributeFamily setImage(File $image)
 * @method LocalizedFallbackValue getLabel(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLabel()
 * @mixin OroEntityConfigBundle_Attribute_Entity_AttributeFamily
 */
#[ORM\Entity(repositoryClass: AttributeFamilyRepository::class)]
#[ORM\Table(name: 'oro_attribute_family')]
#[ORM\UniqueConstraint(name: 'oro_attribute_family_code_org_uidx', columns: ['code', 'organization_id'])]
#[Config(
    defaultValues: [
        'dataaudit' => ['auditable' => true],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'catalog']
    ]
)]
class AttributeFamily implements
    DatesAwareInterface,
    ContextItemInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_attribute_family_label')]
    #[ORM\JoinColumn(name: 'attribute_family_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: [
            'dataaudit' => ['auditable' => true],
            'importexport' => ['order' => 40, 'full' => true, 'fallback_field' => 'string']
        ]
    )]
    protected ?Collection $labels = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    private ?string $code = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    private ?string $entityClass = null;

    /**
     * @var Collection<int, AttributeGroup>
     */
    #[ORM\OneToMany(
        mappedBy: 'attributeFamily',
        targetEntity: AttributeGroup::class,
        cascade: ['ALL'],
        orphanRemoval: true,
        indexBy: 'code'
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $attributeGroups = null;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'is_enabled', type: Types::BOOLEAN, length: 255)]
    private $isEnabled = true;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->attributeGroups = new ArrayCollection();
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $owner
     *
     * @return $this
     */
    public function setOwner(Organization $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param bool $isEnabled
     * @return $this
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
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
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return AttributeFamily
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return ArrayCollection|AttributeGroup[]
     */
    public function getAttributeGroups()
    {
        return $this->attributeGroups;
    }

    /**
     * @param Collection $attributeGroups
     * @return AttributeFamily
     */
    public function setAttributeGroups(Collection $attributeGroups)
    {
        $this->attributeGroups = $attributeGroups;

        return $this;
    }

    /**
     * @param string $code
     * @return null|AttributeGroup
     */
    public function getAttributeGroup($code)
    {
        if (!isset($this->attributeGroups[$code])) {
            return null;
        }

        return $this->attributeGroups[$code];
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @return $this
     */
    public function addAttributeGroup(AttributeGroup $attributeGroup)
    {
        $this->attributeGroups[$attributeGroup->getCode()] = $attributeGroup;
        $attributeGroup->setAttributeFamily($this);

        return $this;
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @return $this
     */
    public function removeAttributeGroup(AttributeGroup $attributeGroup)
    {
        if ($this->attributeGroups->contains($attributeGroup)) {
            $this->attributeGroups->removeElement($attributeGroup);
        }

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getDefaultLabel()->getString();
    }

    #[\Override]
    public function toString()
    {
        return 'code:'.$this->getCode();
    }

    #[\Override]
    public function getHash()
    {
        $data = [];

        /** @var AttributeGroup $group */
        $groups = $this->getAttributeGroups();

        foreach ($groups as $group) {
            $item = ['group' => $group->getId(), 'attributes' => [], 'visible' => $group->getIsVisible()];

            /** @var AttributeGroupRelation $attributeRelation */
            foreach ($group->getAttributeRelations() as $attributeRelation) {
                $item['attributes'][] = $attributeRelation->getId();
            }
            $data[] = $item;
        }

        return md5(serialize([$this->getId() => $data]));
    }
}
